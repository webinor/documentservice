<?php

namespace App\Services;

use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;
use App\Services\Pdf\PdfMetadataService;
use App\Services\Workflow\WorkflowParticipantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentPdfService
{
    protected DocumentEnrichmentManager $documentEnrichmentManager;

    protected PdfMetadataService $metadataService;

    private WorkflowParticipantService $workflowParticipantService;

    public function __construct(
        DocumentEnrichmentManager $documentEnrichmentManager,
        PdfMetadataService $metadataService,
        WorkflowParticipantService $workflowParticipantService
    ) {
        $this->documentEnrichmentManager = $documentEnrichmentManager;
        $this->metadataService = $metadataService;
        $this->workflowParticipantService = $workflowParticipantService;
    }

    /**
     * Génère le PDF d'un document.
     */
    public function generate(
        Document $doc,
        ?string $token = null
    ) {
        /*
        |--------------------------------------------------------------------------
        | Chargement du document
        |--------------------------------------------------------------------------
        */

        $doc->load([
            'document_type',
            'document_references.documentReferenceType',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Enrichissement
        |--------------------------------------------------------------------------
        */

        $document =
            $this->documentEnrichmentManager->enrich($doc);

        /*
        |--------------------------------------------------------------------------
        | Participants / signatures
        |--------------------------------------------------------------------------
        */

        $data =
            $this->workflowParticipantService->getParticipants(
                $document,
                $token
            );

        $participants =
            $data['participants'] ?? [];

        $businessSignatures =
            $data['business_signatures'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Politique de visibilité des signataires
        |--------------------------------------------------------------------------
        */

        $policy =
            SignerVisibilityPolicyFactory::make(
                $document['document_type']['slug']
            );

        $visibleParticipants =
            collect($participants)
                ->filter(
                    fn ($participant) =>
                        $policy->isVisible(
                            $participant,
                            $document
                        )
                )
                ->values()
                ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Template
        |--------------------------------------------------------------------------
        */

        $template =
            $document['document_type']['slug'] ?? null;

        if (
            !$template ||
            !view()->exists("templates.$template")
        ) {
            abort(
                404,
                "Template $template introuvable"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Signatures
        |--------------------------------------------------------------------------
        */

        $signatureDonneur =
            asset('assets/img/signaturearol.jpg');

        $signatureBeneficiaire =
            asset('assets/img/benef.jpg');

        /*
        |--------------------------------------------------------------------------
        | Construction de toutes les signatures
        |--------------------------------------------------------------------------
        */

        $allSignatures =
            collect($visibleParticipants)
                ->map(function ($participant) {

                    return [
                        'type_block' => 'VALIDATION',

                        'user' =>
                            $participant['user'] ?? null,

                        'display_job_title' =>
                            data_get(
                                $participant,
                                'user.active_position.display_job_title'
                            )
                            ?:
                            data_get(
                                $participant,
                                'user.active_position.position.name'
                            )
                            ?: 'Position inconnue',

                        'date' =>
                            $participant['decided_at'] ?? null,

                        'signatureUrl' =>
                            isset(
                                $participant['user']['signature']
                            )
                                ? 
                                // asset(
                                    // 'storage/' .
                                    'http://localhost:8088/storage/'.
                                    $participant['user']['signature']
                                // )
                                : null,
                    ];
                })
                ->merge(
                    collect($businessSignatures)
                        ->map(function ($signature) {

                            return [
                                'type_block' => 'RECEPTION',

                                'user' =>
                                    data_get(
                                        $signature,
                                        'actor.nom_complet'
                                    )
                                    ??
                                    $signature['actor_name']
                                    ??
                                    null,

                                'display_job_title' =>
                                    data_get(
                                        $signature,
                                        'actor.active_position.display_job_title'
                                    )
                                    ?:
                                    data_get(
                                        $signature,
                                        'actor.active_position.position.name'
                                    )
                                    ?: 'Position inconnue',

                                'signature_type' =>
                                    data_get(
                                        $signature,
                                        'signature_type.name'
                                    )
                                    ?? '',

                                'date' =>
                                    $signature['signed_at']
                                    ?? null,

                                'signatureUrl' =>
                                    data_get(
                                        $signature,
                                        'actor.signature'
                                    )
                                        ?
                                        //  asset(
                                            // 'storage/' .
                                            'http://localhost:8088/storage/'.
                                            data_get(
                                                $signature,
                                                'actor.signature'
                                            )
                                        // )
                                        : null,
                            ];
                        })
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Métadonnées
        |--------------------------------------------------------------------------
        */

        $metadata =
            $this->metadataService->build(
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Poste du demandeur
        |--------------------------------------------------------------------------
        */

        $jobTitle =
            data_get(
                $document,
                'actor_details.organization.position.display_job_title'
            )
            ?:
            data_get(
                $document,
                'actor_details.organization.position.position.name'
            );

        /*
        |--------------------------------------------------------------------------
        | Référence comptable
        |--------------------------------------------------------------------------
        */

        $accountingReference =
            optional(
                collect(
                    $document['document_references'] ?? []
                )->firstWhere(
                    'reference_type_code',
                    'ACCOUNTING_ENTRY'
                )
            )['reference'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Génération PDF
        |--------------------------------------------------------------------------
        */

        $pdf =
            Pdf::loadView(
                "templates.$template",
                [
                    'document' =>
                        $document,

                    'accounting_reference' =>
                        $accountingReference,

                    'signatureDonneur' =>
                        $signatureDonneur,

                    'jobTitle' =>
                        $jobTitle,

                    'signatureBeneficiaire' =>
                        $signatureBeneficiaire,

                    'participants' =>
                        $visibleParticipants,

                    'business_signatures' =>
                        $businessSignatures,

                    'allSignatures' =>
                        $allSignatures,

                    'metadata' =>
                        $metadata,
                ]
            );

        return [
            'pdf' => $pdf,

            'content' =>
                $pdf->output(),

            'document' =>
                $document,

            'template' =>
                $template,

            'file_name' =>
                $template .
                '-' .
                ($document['reference'] ?? $doc->uuid) .
                '.pdf',
        ];
    }
}