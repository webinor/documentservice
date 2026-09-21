<?php

namespace App\Services;

use App\DTO\LeaveCalculationRequest;
use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;
use App\Services\Absence\LeaveCalculatorService;
use App\Services\Pdf\PdfMetadataService;
use App\Services\Workflow\WorkflowParticipantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentPdfService
{
    protected DocumentEnrichmentManager $documentEnrichmentManager;

    protected PdfMetadataService $metadataService;

    private WorkflowParticipantService $workflowParticipantService;

    private LeaveCalculatorService $leaveCalculatorService;


    public function __construct(
        DocumentEnrichmentManager $documentEnrichmentManager,
        PdfMetadataService $metadataService,
        WorkflowParticipantService $workflowParticipantService,
        LeaveCalculatorService $leaveCalculatorService
    ) {
        $this->documentEnrichmentManager = $documentEnrichmentManager;
        $this->metadataService = $metadataService;
        $this->workflowParticipantService = $workflowParticipantService;
        $this->leaveCalculatorService = $leaveCalculatorService;
    }

    /**
     * Génère le PDF d'un document.
     */
    public function generate(
        Document $doc,
        ?string $token = null,
        ?string $context = null,
        ?array $contextualData = []
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

     

        $document = $this->documentEnrichmentManager->enrich($doc , null);

            
        // throw new Exception(json_encode($document['absence_request']['leave_type']), 1);
        
       

        /*
        |--------------------------------------------------------------------------
        | Participants / signatures
        |--------------------------------------------------------------------------
        */

        $data=
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

            if ($context) {
                $document['context'] = $context;
            }
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

        // $template = $context ?? 
        //     $document['document_type']['slug'] ?? null;

        

        // if (
        //     !$template ||
        //     !view()->exists("templates.$template")
        // ) {
        //     abort(
        //         404,
        //         "Template $template introuvable"
        //     );
        // }

         /*
    |--------------------------------------------------------------------------
    | Template selon le document demandé
    |--------------------------------------------------------------------------
    */

    $template = $this->getLeaveTemplate(
        $context,  $document['document_type']['slug'] 
    );

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
        

            $baseVariables = [
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
                ];
            /*
|--------------------------------------------------------------------------
| Variables contextuelles
|--------------------------------------------------------------------------
*/

        // throw new Exception(json_encode($document['absence_request']['leave_type']), 1);


$documentTypeSlug =
    $document['document_type']['slug'] ?? '';

$contextualVariables =
    $this->getContextualVariables(
        $documentTypeSlug,
        $context,
        $document,
        $contextualData
    );

        // throw new Exception(json_encode($document['absence_request']['leave_type']), 1);


        /*
        |--------------------------------------------------------------------------
        | Génération PDF
        |--------------------------------------------------------------------------
        */

        $pdf =
            Pdf::loadView(
                "templates.$template",
                array_merge($baseVariables,$contextualVariables)
                // [
                //     'document' =>
                //         $document,

                //     'accounting_reference' =>
                //         $accountingReference,

                //     'signatureDonneur' =>
                //         $signatureDonneur,

                //     'jobTitle' =>
                //         $jobTitle,

                //     'signatureBeneficiaire' =>
                //         $signatureBeneficiaire,

                //     'participants' =>
                //         $visibleParticipants,

                //     'business_signatures' =>
                //         $businessSignatures,

                //     'allSignatures' =>
                //         $allSignatures,

                //     'metadata' =>
                //         $metadata,
                // ]
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

    protected function getLeaveTemplate(
    string $context,
    string $documentTypeSlug
): string {


    switch ($context) {

        // case 'leave_validated':
        //     return 'leave-request-validated';

        case 'leave_order':
            return 'leave-order';

        default:
            return $documentTypeSlug;
            throw new \InvalidArgumentException(
                "Contexte de congé inconnu : {$context}"
            );
    }
}

/**
 * Construit les variables contextuelles selon le type de document.
 *
 * Ces variables sont ajoutées aux données communes envoyées
 * au template Blade.
 */
protected function getContextualVariables(
    string $documentTypeSlug,
    ?string $context,
    array $document,
    array $contextualData 
): array {
    switch ($documentTypeSlug) {

        /*
        |--------------------------------------------------------------------------
        | Congés
        |--------------------------------------------------------------------------
        */
        case 'demande-d-absence':


             if (($context == 'leave_request_validated')) {
            
        // throw new Exception(json_encode($document['absence_request']), 1);
        // throw new Exception(json_encode($context), 1);
        

        }
        else{

        // throw new \Exception(json_encode($document['absence_request']), 1);


        }

            $getLeaveContextVariables = $this->getLeaveContextVariables(
                $context,
                $document,
                $contextualData
            );

        // throw new Exception(json_encode($document['absence_request']['leave_type']), 1);


            // throw new Exception(json_encode($getLeaveContextVariables), 1);
            

            return $getLeaveContextVariables;

        /*
        |--------------------------------------------------------------------------
        | Papier Taxi
        |--------------------------------------------------------------------------
        */
        case 'taxi_paper':

            return [];
            
            $this->getTaxiPaperContextVariables(
                $context,
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Note de frais
        |--------------------------------------------------------------------------
        */
        case 'fee_note':

            return [];
            $this->getFeeNoteContextVariables(
                $context,
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Fiche à régulariser
        |--------------------------------------------------------------------------
        */
        case 'regularization_sheet':

            return [];
            
            $this->getRegularizationSheetContextVariables(
                $context,
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Aucun contexte spécifique
        |--------------------------------------------------------------------------
        */
        default:

            return [];
    }
}

/**
 * Variables spécifiques aux documents de congé.
 */
protected function getLeaveContextVariables(
    ?string $context,
    array $document,
    array $contextualData
): array {


        // throw new Exception(json_encode($document['absence_request']['leave_type']), 1);
 

    switch ($context) {

    
    
    case 'leave_order':



//         throw new Exception(
//     json_encode(
//         $document['absence_request'] ?? null,
//         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
//     ),
//     1
// );

        $absence = $document['absence_request'];


        // throw new Exception(json_encode($absence), 1);


      

//    $calculationRequest =
//               new LeaveCalculationRequest([

//     'leave_type_id' =>
//         $absence['leave_type_id'],

//     'start_date' =>
//         $absence['departure_date'],

//     'end_date' =>
//         $absence['return_date'],

//     'start_time' =>
//         $absence['departure_time'],

//     'end_time' =>
//         $absence['return_time'],

//     'employee_id' =>
//         $document['actor_id'],
// ]);

  $simulation = 
  $absence['simulation'];
                // $this->leaveCalculatorService->calculateWithBalance(
                //     $calculationRequest,
                //     request()->bearerToken()
                // );


                     $resumptionDate = Carbon::parse(
    data_get(
                $simulation,
                'resumption_date'
            )
);

// throw new Exception(json_encode(Carbon::parse($contextualData['executed_at'])), 1);




    return array_merge(

        [
            'document_context' =>
                'leave_order',

            'is_leave_order' =>
                true,

            'is_leave_request_validated' =>
                false,

            'employeeCivility' => $document['actor_details']['full_civilite'],

            'employeeName' => 
                trim(
                    ($document['actor_details']['nom'] ?? '') .
                    ' ' .
                    ($document['actor_details']['prenom'] ?? '')
                ),

            'jobTitle' =>
                data_get(
                    $document,
                    'actor_details.organization.position.display_job_title'
                )
                ?:
                data_get(
                    $document,
                    'actor_details.organization.position.position.name'
                )
                ?:
                '-',

            'departureDate' =>
                data_get(
                    $document,
                    'absence_request.departure_date'
                ),

            'returnDate' =>
                data_get(
                    $document,
                    'absence_request.return_date'
                ),

            'resumptionDate' =>$resumptionDate ,

            'leaveType' =>
                ucfirst(
                    strtolower(
                        data_get(
                            $document,
                            'absence_request.leave_type.name',
                            '-'
                        )
                    )
                ),

            'documentDate' => Carbon::parse($contextualData['executed_at'])->format('d/m/Y') ??
                now()->format('d/m/Y'),


                  /*
        |--------------------------------------------------------------------------
        | DOCUMENT
        |--------------------------------------------------------------------------
        */

        'document' => [

            'type' =>
                'LETTRE DE MISE EN CONGÉ',

            'code' =>
                'RH / CONGÉ',

            'reference' =>
                data_get(
                    $document,
                    'reference'
                ),

            'confidentiality' =>
                'DOCUMENT INTERNE',
        ],
        ],

        [
            'company' =>
                $this->getCompanyContext(),

            'branding' =>
                $this->getBrandingContext(),
        ]

    );
        case 'leave_request_validated':

            return [];
            
            [
                'document_context' =>
                    'leave_request_validated',

                'is_leave_order' => false,

                'is_leave_request_validated' => true,

                'leave_employee' =>
                    data_get(
                        $document,
                        'actor_details'
                    ),

                'leave_start_date' =>
                    data_get(
                        $document,
                        'data.start_date'
                    ),

                'leave_end_date' =>
                    data_get(
                        $document,
                        'data.end_date'
                    ),
            ];

        default:

            return [
                'document_context' => $context,
            ];
    }
}

protected function getCompanyContext(): array
{
    return [
        'name' =>
            'CAMEROUN ASSISTANCE SANITAIRE',

        'short_name' =>
            'CAS',

        'tagline' =>
            'Nous sommes là quand il le faut !',

        'address' =>
            '491 Rue Koumassi – Douala – Bali, Cameroun',

        'phone' =>
            '+237 233 43 30 30',

        'support_phone' =>
            '+237 699 90 20 20',

        'emergency_phone' =>
            '+237 85 75',

        'email' =>
            'contact@cas-assistance.com',

        'website' =>
            'www.cas-assistance.com',

        'website_url' =>
            'https://cas-assistance.com',

        'services' =>
            'ASSISTANCE AUX PERSONNES • TRANSPORTS MÉDICALISÉS • ÉVACUATIONS SANITAIRES',

        'founded_year' =>
            '1987',
    ];
}

protected function getBrandingContext(): array
{
    return [
        'primary_color' => '#123B63',
        'secondary_color' => '#0B2945',
        'accent_color' => '#C6202E',
        'light_color' => '#F4F7FA',
        'border_color' => '#D9E1E8',
        'text_color' => '#202830',
        'muted_color' => '#687784',
    ];
}
}