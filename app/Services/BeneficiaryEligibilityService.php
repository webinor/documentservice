<?php

namespace App\Services;

use App\Models\Misc\Document;
use App\Services\Resolvers\WorkflowStatusResolver;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class BeneficiaryEligibilityService
{
  
    /**
     * Nombre maximum de fiches incomplètes autorisées.
     *
     * Le blocage commence à la troisième fiche.
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
     * Une fiche compte uniquement lorsque :
     * - son type est REGULARIZATION_SHEET ;
     * - son workflow est PENDING ;
     * - au moins un justificatif physique est false ou null.
     *
     * Une fiche COMPLETE ne compte jamais, même si les données
     * physiques enregistrées en base semblent incohérentes.
     *
     * @param int $beneficiaryId
     * @return array
     */
    public function evaluate(int $beneficiaryId): array
    {
        if ($beneficiaryId <= 0) {
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

        /*
         * 3. Récupérer les identifiants des documents.
         *
         * Un seul appel batch sera effectué pour ces documents,
         * au lieu d'un appel HTTP par fiche.
         */
        $documentIds = $documentsWithSheets
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $workflowInstances = empty($documentIds)
            ? []
            : $this->workflowServiceClient
                ->getInstancesByDocumentIds($documentIds);

        // throw new Exception(json_encode(collect($workflowInstances)->pluck('id')), 1);
        
        /*
         * 4. Indexer les workflows par document_id.
         */
        $workflowsByDocument = collect($workflowInstances)
            ->groupBy(function ($workflow) {
                return isset($workflow['document_id'])
                    ? (int) $workflow['document_id']
                    : 0;
            });
        // throw new Exception(json_encode(array_keys($workflowsByDocument->toArray())), 1);
        

        $pendingSheets = [];

        $completedSheets = [];

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
             * Une instance COMPLETE prévaut sur toute ancienne
             * instance PENDING associée au même document.
             */
            $completeWorkflows = $documentWorkflows
                ->filter(function ($workflow) {
                    return isset($workflow['status'])
                        && strtoupper($workflow['status'])
                            === self::WORKFLOW_COMPLETE;
                })
                ->values();

            

            if ($completeWorkflows->isNotEmpty()) {
                $completedSheets[] = [
                    'document_id' => (int) $document->id,
                    'workflow_status' => $this->workflowStatusResolver->resolve(self::WORKFLOW_COMPLETE),
                    'counted' => false,
                    'reason_code' => 'WORKFLOW_COMPLETE',
                    'reason' => 'Le workflow est clôturé. '
                        . 'Les justificatifs sont considérés comme reçus.',
                ];

                continue;
            }

            /*
             * Rechercher une instance PENDING.
             */
            $pendingWorkflows = $documentWorkflows
                ->filter(function ($workflow) {
                    return isset($workflow['status'])
                        && strtoupper($workflow['status'])
                            === self::WORKFLOW_PENDING;
                })
                ->values();

            

            if ($pendingWorkflows->isEmpty()) {
                if ($documentWorkflows->isEmpty()) {
                    $missingWorkflowSheets[] = [
                        'document_id' => (int) $document->id,
                        'counted' => false,
                        'reason_code' => 'WORKFLOW_INSTANCE_NOT_FOUND',
                        'reason' => 'Aucune instance de workflow trouvée.',
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
                        'reason' => 'Le workflow ne possède pas le statut PENDING.',
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
                $missingWorkflowSheets[] = [
                    'document_id' => (int) $document->id,
                    'counted' => false,
                    'reason_code' => 'WORKFLOW_DOCUMENT_MISMATCH',
                    'reason' => 'L’identifiant du document ne correspond pas au workflow.',
                ];

              

            // throw new Exception(json_encode("WORKFLOW_DOCUMENT_MISMATCH"), 1);


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

            /*
             * Par définition, il faut au moins un justificatif
             * explicitement en attente pour compter la fiche.
             *
             * Une fiche sans justificatif ne satisfait donc pas
             * automatiquement cette condition.
             */

              if ($workflow['id'] == 1405) {

            $user_connected = request()->get("user"); // récupéré du user-service

                
            // throw new Exception(json_encode($user_connected), 1);
            // throw new Exception(json_encode($pendingReceipts), 1);
            
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
                'workflow_status' => $this->workflowStatusResolver->resolve(self::WORKFLOW_PENDING) ,
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

        // throw new Exception(json_encode($pendingSheets), 1);
        

        $blocked = $pendingCount
            >= self::MAX_PENDING_REGULARIZATIONS;

        $blockingThreshold =
            self::MAX_PENDING_REGULARIZATIONS + 1;

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
                        'Ce bénéficiaire possède %d fiches à régulariser '
                        . "Incomplètes : un ou plusieurs justificatifs n’ont pas encore été transmis."
                        . 'Le maximum autorisé est de %d.',
                        $pendingCount,
                        self::MAX_PENDING_REGULARIZATIONS
                    )
                    : sprintf(
                        'Ce bénéficiaire possède %d fiche(s) à régulariser '
                         . "Incomplètes : un ou plusieurs justificatifs n’ont pas encore été transmis."
                        // . 'incomplète(s) dont le workflow est PENDING. '
                        . 'Le maximum autorisé est de %d.',
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

                    'completed_sheets_ignored' =>
                        count($completedSheets),

                    'other_status_sheets_ignored' =>
                        count($otherStatusSheets),

                    'missing_workflow_sheets' =>
                        count($missingWorkflowSheets),
                ],

                'affected_document_types' =>
                    self::AFFECTED_DOCUMENT_TYPES,

                'pending_regularizations' => $pendingSheets,

                'completed_regularizations' => $completedSheets,

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