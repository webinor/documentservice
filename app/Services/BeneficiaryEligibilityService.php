<?php

namespace App\Services;

use App\Models\Misc\Document;
use App\Services\Resolvers\WorkflowStatusResolver;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Throwable;

class BeneficiaryEligibilityService
{
    /**
     * Nombre maximum de fiches incomplètes autorisées.
     *
     * Le blocage commence lorsque le bénéficiaire atteint
     * ce nombre de fiches en attente.
     */
    const MAX_PENDING_REGULARIZATIONS = 3;

    /**
     * Statuts de workflow utilisés par la règle métier.
     */
    const WORKFLOW_PENDING = 'PENDING';

    const WORKFLOW_COMPLETE = 'COMPLETE';

    /**
     * Types de documents concernés par la règle.
     */
    const AFFECTED_DOCUMENT_TYPES = [
        'taxi_paper',
        'regularization_sheet',
        'fee_note',
    ];

    /**
     * Client du Workflow Service.
     *
     * @var WorkflowServiceClient
     */
    protected $workflowServiceClient;

    /**
     * Résout les URL frontend des documents.
     *
     * @var DocumentUrlResolver
     */
    protected $documentUrlResolver;

    /**
     * Résolveur des statuts du workflow.
     *
     * @var WorkflowStatusResolver
     */
    protected WorkflowStatusResolver $workflowStatusResolver;

    public function __construct(
        WorkflowStatusResolver $workflowStatusResolver,
        WorkflowServiceClient $workflowServiceClient,
        DocumentUrlResolver $documentUrlResolver
    ) {
        $this->workflowServiceClient = $workflowServiceClient;
        $this->workflowStatusResolver = $workflowStatusResolver;
        $this->documentUrlResolver = $documentUrlResolver;
    }

    /**
     * Détermine si un bénéficiaire peut recevoir un nouveau document.
     *
     * Le Workflow Service retourne uniquement les instances
     * admissibles selon les critères métier configurés côté workflow.
     *
     * Une fiche compte uniquement lorsque :
     * - son type est REGULARIZATION_SHEET ;
     * - son workflow retourné est PENDING ;
     * - au moins un justificatif physique est false ou null.
     *
     * Les workflows COMPLETE ne sont plus recherchés ni filtrés
     * ici : leur admissibilité est gérée par le Workflow Service.
     *
     * @param int $beneficiaryId
     * @return array
     *
     * @throws Throwable
     */
    public function evaluate(int $beneficiaryId): array
    {
        Log::info(
            '[BeneficiaryEligibility] Début de la vérification d’éligibilité.',
            [
                'beneficiary_id' => $beneficiaryId,
                'max_pending_regularizations' =>
                    self::MAX_PENDING_REGULARIZATIONS,
                'checked_at' => now()->toDateTimeString(),
            ]
        );

        if ($beneficiaryId <= 0) {
            Log::warning(
                '[BeneficiaryEligibility] Vérification impossible : identifiant bénéficiaire invalide.',
                [
                    'beneficiary_id' => $beneficiaryId,
                ]
            );

            return $this->buildInvalidBeneficiaryResponse(
                $beneficiaryId
            );
        }

        /*
         * 1. Récupérer les documents de type Fiche à régulariser
         * appartenant à ce bénéficiaire.
         *
         * actor_id identifie le bénéficiaire.
         *
         * Les justificatifs sont chargés en eager loading pour
         * éviter une requête SQL par fiche.
         */
        $documents = Document::query()
            ->where('actor_id', $beneficiaryId)
            ->whereHas('document_type', function ($query) {
                $query->where(
                    'code',
                    'REGULARIZATION_SHEET'
                );
            })
            ->with([
                'document_type',
                'regularization_sheet.receipts',
            ])
            ->get();

        /*
         * 2. Identifier les documents possédant une fiche
         * à régulariser.
         */
        $documentsWithSheets = $documents
            ->filter(function ($document) {
                return $document->regularization_sheet !== null;
            })
            ->values();

        Log::info(
            '[BeneficiaryEligibility] Documents récupérés.',
            [
                'beneficiary_id' => $beneficiaryId,
                'total_documents_found' => $documents->count(),
                'documents_with_regularization_sheet' =>
                    $documentsWithSheets->count(),
                'documents_without_regularization_sheet' =>
                    $documents->count() - $documentsWithSheets->count(),
            ]
        );

        /*
         * 3. Récupérer les identifiants des documents.
         *
         * Un seul appel batch est effectué pour ces documents,
         * au lieu d'un appel HTTP par fiche.
         */
        $documentIds = $documentsWithSheets
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        Log::info(
            '[BeneficiaryEligibility] Début de la récupération des workflows.',
            [
                'beneficiary_id' => $beneficiaryId,
                'document_count' => count($documentIds),
                'document_ids' => $documentIds,
            ]
        );

        try {
            $workflowInstances = empty($documentIds)
                ? []
                : $this->workflowServiceClient
                    ->getInstancesByDocumentIds($documentIds);
        } catch (Throwable $exception) {
            Log::error(
                '[BeneficiaryEligibility] Échec de la récupération des workflows.',
                [
                    'beneficiary_id' => $beneficiaryId,
                    'document_count' => count($documentIds),
                    'document_ids' => $documentIds,
                    'exception_class' => get_class($exception),
                    'exception_message' => $exception->getMessage(),
                ]
            );

            // Conserver le comportement initial : propager l'exception.
            throw $exception;
        }

        Log::info(
            '[BeneficiaryEligibility] Workflows récupérés.',
            [
                'beneficiary_id' => $beneficiaryId,
                'documents_to_check' => count($documentIds),
                'workflow_instances_found' =>
                    collect($workflowInstances)->count(),
            ]
        );

        /*
         * 4. Indexer les workflows par document_id.
         */
        $workflowsByDocument = collect($workflowInstances)
            ->groupBy(function ($workflow) {
                return isset($workflow['document_id'])
                    ? (int) $workflow['document_id']
                    : 0;
            });

        $pendingSheets = [];

        $otherStatusSheets = [];

        $missingWorkflowSheets = [];

        /*
         * 5. Examiner chaque fiche à régulariser.
         */
        foreach ($documentsWithSheets as $document) {
            $sheet = $document->regularization_sheet;

            $documentWorkflows = $workflowsByDocument->get(
                (int) $document->id,
                collect()
            );

            /*
             * Le Workflow Service retourne uniquement les workflows
             * admissibles. On ne recherche donc plus les instances
             * COMPLETE et on ne les traite plus séparément.
             *
             * On recherche directement une instance PENDING.
             */
            $pendingWorkflows = $documentWorkflows
                ->filter(function ($workflow) {
                    return isset($workflow['status'])
                        && strtoupper($workflow['status'])
                            === self::WORKFLOW_PENDING;
                })
                ->values();

            if ($pendingWorkflows->isEmpty()) {
                Log::debug(
                    '[BeneficiaryEligibility] Fiche non comptabilisée : aucun workflow PENDING.',
                    [
                        'beneficiary_id' => $beneficiaryId,
                        'document_id' => (int) $document->id,
                        'document_uuid' => $document->uuid ?? null,
                        'workflow_count' => $documentWorkflows->count(),
                        'workflow_statuses' => $documentWorkflows
                            ->pluck('status')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                        'reason_code' => $documentWorkflows->isEmpty()
                            ? 'WORKFLOW_INSTANCE_NOT_FOUND'
                            : 'WORKFLOW_NOT_PENDING',
                    ]
                );

                if ($documentWorkflows->isEmpty()) {
                    $missingWorkflowSheets[] = [
                        'document_id' => (int) $document->id,
                        'counted' => false,
                        'reason_code' => 'WORKFLOW_INSTANCE_NOT_FOUND',
                        'reason' => 'Aucune instance de workflow admissible trouvée.',
                    ];
                } else {
                    $otherStatusSheets[] = [
                        'document_id' => (int) $document->id,
                        'workflow_statuses' => $documentWorkflows
                            ->pluck('status')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all(),
                        'counted' => false,
                        'reason_code' => 'WORKFLOW_NOT_PENDING',
                        'reason' => 'Aucun workflow admissible ne possède le statut PENDING.',
                    ];
                }

                continue;
            }

            /*
             * Vérifier la cohérence de l'identifiant du document.
             *
             * Les détails des justificatifs ne doivent pas être
             * associés à une autre instance de document.
             */
            $workflow = $pendingWorkflows->first();

            if (
                isset($workflow['document_uuid'])
                && isset($document->uuid)
                && $workflow['document_uuid'] !== $document->uuid
            ) {
                Log::warning(
                    '[BeneficiaryEligibility] Incohérence entre le document et le workflow.',
                    [
                        'beneficiary_id' => $beneficiaryId,
                        'document_id' => (int) $document->id,
                        'document_uuid' => $document->uuid ?? null,
                        'workflow_id' => $workflow['id'] ?? null,
                        'workflow_document_uuid' =>
                            $workflow['document_uuid'] ?? null,
                        'reason_code' => 'WORKFLOW_DOCUMENT_MISMATCH',
                    ]
                );

                $missingWorkflowSheets[] = [
                    'document_id' => (int) $document->id,
                    'counted' => false,
                    'reason_code' => 'WORKFLOW_DOCUMENT_MISMATCH',
                    'reason' => 'L’identifiant du document ne correspond pas au workflow.',
                ];

                continue;
            }

            /*
             * Récupérer les justificatifs de cette fiche.
             */
            $receipts = $sheet->receipts
                ?? collect();

            $totalReceipts = $receipts->count();

            $receivedReceipts = $receipts
                ->filter(function ($receipt) {
                    return $receipt->physical_receipt_received === true
                        || $receipt->physical_receipt_received === 1
                        || $receipt->physical_receipt_received === '1';
                })
                ->count();

            /*
             * Un justificatif est en attente lorsque sa valeur
             * est false ou null.
             *
             * Les valeurs inconnues ne sont pas assimilées à
             * une réception confirmée.
             */
            $pendingReceipts = $receipts
                ->filter(function ($receipt) {
                    $received = $receipt->physical_receipt_received;

                    return $received === null
                        || $received === false
                        || $received === 0
                        || $received === '0';
                })
                ->values();

            Log::debug(
                '[BeneficiaryEligibility] Analyse des justificatifs physiques.',
                [
                    'beneficiary_id' => $beneficiaryId,
                    'document_id' => (int) $document->id,
                    'document_uuid' => $document->uuid ?? null,
                    'document_reference' => $document->reference ?? null,
                    'workflow_id' => $workflow['id'] ?? null,
                    'workflow_status' => $workflow['status'] ?? null,
                    'total_receipts' => $totalReceipts,
                    'received_receipts' => $receivedReceipts,
                    'pending_receipts' => $pendingReceipts->count(),
                ]
            );

            /*
             * Par définition, il faut au moins un justificatif
             * explicitement en attente pour compter la fiche.
             *
             * Une fiche sans justificatif ne satisfait donc pas
             * automatiquement cette condition.
             */

            if ($workflow['id'] == 1405) {
                $user_connected = request()->get('user');

                // Conserver ici tes éventuelles vérifications existantes.
            }

            if ($pendingReceipts->isEmpty()) {
                // continue;
            }

            /*
             * Construire le détail des justificatifs en attente.
             */
            $pendingReceiptDetails = $pendingReceipts
                ->map(function ($receipt) {
                    return [
                        'receipt_id' => (int) $receipt->id,
                        'code' => $receipt->code,
                        'reference' => $receipt->reference,
                        'physical_receipt_received' =>
                            $receipt->physical_receipt_received,
                        'physical_receipt_received_at' =>
                            $receipt->physical_receipt_received_at,
                        'reason' => 'Justificatif physique non confirmé comme reçu.',
                    ];
                })
                ->values()
                ->all();

            /*
             * Journaliser la fiche avant son ajout au décompte.
             */
            Log::info(
                '[BeneficiaryEligibility] Fiche ajoutée au décompte des fiches en attente.',
                [
                    'beneficiary_id' => $beneficiaryId,
                    'document_id' => (int) $document->id,
                    'document_uuid' => $document->uuid ?? null,
                    'document_reference' => $document->reference ?? null,
                    'workflow_id' => $workflow['id'] ?? null,
                    'total_receipts' => $totalReceipts,
                    'received_receipts' => $receivedReceipts,
                    'pending_receipts' => $pendingReceipts->count(),
                ]
            );

            /*
             * La fiche compte une seule fois, même si plusieurs
             * justificatifs physiques sont en attente.
             */
            $pendingSheets[] = [
                'document_id' => (int) $document->id,
                'document_uuid' => $document->uuid ?? null,
                'document_title' => $document->title ?? null,
                'document_reference' => $document->reference ?? null,

                // URL frontend calculée selon le type et les permissions.
                'document_url' => $this->documentUrlResolver->resolve(
                    $document
                ),

                'document_type' => 'REGULARIZATION_SHEET',
                'workflow_instance_id' => $workflow['id'] ?? null,
                'workflow_status' =>
                    $this->workflowStatusResolver->resolve(
                        self::WORKFLOW_PENDING
                    ),
                'incomplete' => true,
                'counted' => true,
                'total_receipts' => $totalReceipts,
                'received_receipts' => $receivedReceipts,
                'pending_receipts' => $pendingReceipts->count(),
                'pending_receipt_details' => $pendingReceiptDetails,
                'reason_code' => 'PHYSICAL_RECEIPTS_PENDING',
                'reason' => sprintf(
                    '%d justificatif(s) physique(s) en attente de réception.',
                    $pendingReceipts->count()
                ),
            ];
        }

        /*
         * 6. Calculer la décision.
         */
        $pendingCount = count($pendingSheets);

        $blocked = $pendingCount
            >= self::MAX_PENDING_REGULARIZATIONS;

        $blockingThreshold =
            self::MAX_PENDING_REGULARIZATIONS + 1;

        /*
         * Préparer les informations de diagnostic.
         */
        $pendingDocumentIds = collect($pendingSheets)
            ->pluck('document_id')
            ->values()
            ->all();

        $eligibilityContext = [
            'beneficiary_id' => $beneficiaryId,
            'eligible' => !$blocked,
            'blocked' => $blocked,
            'decision' => $blocked
                ? 'BLOCKED'
                : 'AUTHORIZED',
            'reason_code' => $blocked
                ? 'TOO_MANY_PENDING_REGULARIZATIONS'
                : 'WITHIN_ALLOWED_THRESHOLD',
            'pending_incomplete_sheets' => $pendingCount,
            'maximum_allowed' =>
                self::MAX_PENDING_REGULARIZATIONS,
            'blocking_threshold' => $blockingThreshold,
            'pending_document_ids' => $pendingDocumentIds,
            'other_status_sheets_ignored' => count($otherStatusSheets),
            'missing_workflow_sheets' => count($missingWorkflowSheets),
        ];

        /*
         * LOG CRITIQUE :
         * Le bénéficiaire ne peut plus créer de document
         * lorsque $blocked vaut true.
         */
        if ($blocked) {
            Log::warning(
                '[BeneficiaryEligibility] BÉNÉFICIAIRE BLOQUÉ : création de document interdite.',
                $eligibilityContext
            );
        } else {
            Log::info(
                '[BeneficiaryEligibility] Bénéficiaire autorisé à créer un document.',
                $eligibilityContext
            );
        }

        /*
         * 7. Construire une réponse détaillée.
         */
        return [
            'success' => true,
            'data' => [
                'beneficiary_id' => $beneficiaryId,
                'eligible' => !$blocked,
                'blocked' => $blocked,
                'decision' => $blocked
                    ? 'BLOCKED'
                    : 'AUTHORIZED',

                'reason_code' => $blocked
                    ? 'TOO_MANY_PENDING_REGULARIZATIONS'
                    : 'WITHIN_ALLOWED_THRESHOLD',

                'reason' => $blocked
                    ? sprintf(
                        'Ce bénéficiaire possède %d fiche(s) à régulariser incomplète(s) : un ou plusieurs justificatifs n’ont pas encore été transmis. Le maximum autorisé est de %d.',
                        $pendingCount,
                        self::MAX_PENDING_REGULARIZATIONS
                    )
                    : sprintf(
                        'Ce bénéficiaire possède %d fiche(s) à régulariser incomplète(s) : un ou plusieurs justificatifs n’ont pas encore été transmis. Le maximum autorisé est de %d.',
                        $pendingCount,
                        self::MAX_PENDING_REGULARIZATIONS
                    ),

                'summary' => [
                    'total_regularization_documents' =>
                        $documentsWithSheets->count(),

                    'pending_incomplete_sheets' =>
                        $pendingCount,

                    'maximum_allowed' =>
                        self::MAX_PENDING_REGULARIZATIONS,

                    'blocking_threshold' =>
                        $blockingThreshold,

                    'excess_sheets' => max(
                        0,
                        $pendingCount
                            - self::MAX_PENDING_REGULARIZATIONS
                    ),

                    'other_status_sheets_ignored' =>
                        count($otherStatusSheets),

                    'missing_workflow_sheets' =>
                        count($missingWorkflowSheets),
                ],

                'affected_document_types' =>
                    self::AFFECTED_DOCUMENT_TYPES,

                'pending_regularizations' => $pendingSheets,

                'other_status_regularizations' => $otherStatusSheets,

                'regularizations_without_workflow' =>
                    $missingWorkflowSheets,
            ],
        ];
    }

    /**
     * Refuse la création d'un document si le bénéficiaire est bloqué.
     *
     * En cas d'indisponibilité du Workflow Service, l'exception
     * remonte : on ne doit pas autoriser une création sur la base
     * d'un contrôle incomplet.
     *
     * @param int $beneficiaryId
     * @return array
     */
    public function assertEligible(int $beneficiaryId): array
    {
        $result = $this->evaluate($beneficiaryId);

        if (
            empty($result['success'])
            || empty($result['data']['eligible'])
        ) {
            Log::warning(
                '[BeneficiaryEligibility] REFUS DE CRÉATION : assertEligible() bloque la requête.',
                [
                    'beneficiary_id' => $beneficiaryId,
                    'reason_code' => $result['data']['reason_code']
                        ?? 'BENEFICIARY_NOT_ELIGIBLE',
                    'reason' => $result['data']['reason'] ?? null,
                    'decision' => $result['data']['decision'] ?? null,
                    'pending_incomplete_sheets' =>
                        $result['data']['summary']['pending_incomplete_sheets']
                        ?? null,
                    'maximum_allowed' =>
                        $result['data']['summary']['maximum_allowed']
                        ?? self::MAX_PENDING_REGULARIZATIONS,
                    'pending_document_ids' => collect(
                        $result['data']['pending_regularizations'] ?? []
                    )->pluck('document_id')->values()->all(),
                ]
            );

            throw new HttpResponseException(
                response()->json(
                    [
                        'success' => false,
                        'code' => $result['data']['reason_code']
                            ?? 'BENEFICIARY_NOT_ELIGIBLE',
                        'message' => $result['data']['reason']
                            ?? 'L’éligibilité du bénéficiaire n’a pas pu être confirmée.',
                        'data' => $result['data'] ?? [],
                    ],
                    422
                )
            );
        }

        Log::info(
            '[BeneficiaryEligibility] Création autorisée par assertEligible().',
            [
                'beneficiary_id' => $beneficiaryId,
                'decision' => $result['data']['decision'] ?? null,
                'pending_incomplete_sheets' =>
                    $result['data']['summary']['pending_incomplete_sheets']
                    ?? null,
                'maximum_allowed' =>
                    $result['data']['summary']['maximum_allowed']
                    ?? self::MAX_PENDING_REGULARIZATIONS,
            ]
        );

        return $result;
    }

    /**
     * Réponse en cas d'identifiant de bénéficiaire invalide.
     */
    protected function buildInvalidBeneficiaryResponse(
        int $beneficiaryId
    ): array {
        return [
            'success' => false,
            'data' => [
                'beneficiary_id' => $beneficiaryId,
                'eligible' => false,
                'blocked' => true,
                'decision' => 'BLOCKED',
                'reason_code' => 'INVALID_BENEFICIARY_ID',
                'reason' => 'L’identifiant du bénéficiaire est invalide.',
                'summary' => [
                    'pending_incomplete_sheets' => null,
                    'maximum_allowed' =>
                        self::MAX_PENDING_REGULARIZATIONS,
                    'blocking_threshold' =>
                        self::MAX_PENDING_REGULARIZATIONS + 1,
                ],
                'affected_document_types' =>
                    self::AFFECTED_DOCUMENT_TYPES,
                'pending_regularizations' => [],
            ],
        ];
    }
}