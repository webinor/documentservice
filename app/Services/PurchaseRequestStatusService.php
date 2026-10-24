<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseRequestStatusService
{
    /**
     * Détermine le statut de la demande d'achat à partir
     * du résultat de l'action exécutée dans le workflow.
     *
     * IMPORTANT :
     * Les statuts cibles ci-dessous sont des exemples.
     * Ils doivent correspondre aux statuts réellement utilisés
     * par ton modèle PurchaseRequest et par ta base de données.
     *
     * @param PurchaseRequest $purchaseRequest
     * @param array $workflowContext
     * @return array
     */
    public function updateFromWorkflow(
        PurchaseRequest $purchaseRequest,
        array $workflowContext
    ): array {
        $workflowStatus = strtoupper(
            trim($workflowContext['workflow_instance_step_status'])
        );

        $workflowAction = strtoupper(
            trim($workflowContext['workflow_action'])
        );

        /*
         * Le Document Service décide du statut cible.
         * Le Workflow Service ne transmet jamais ce statut.
         */
        $newStatus = $this->determineStatus(
            $workflowStatus,
            $workflowAction
        );

        return DB::transaction(function () use (
            $purchaseRequest,
            $newStatus,
            $workflowContext
        ) {
            /*
             * Recharger la demande dans la transaction pour éviter
             * de travailler sur une instance devenue obsolète.
             */
            $purchaseRequest = PurchaseRequest::whereKey(
                $purchaseRequest->getKey()
            )->lockForUpdate()->firstOrFail();

            $previousStatus = $purchaseRequest->status;

            /*
             * Éviter une écriture inutile si le statut est déjà correct.
             */
            if ($previousStatus !== $newStatus) {
                $purchaseRequest->status = $newStatus;
                $purchaseRequest->save();
            }

            return [
                'purchase_request_id' => $purchaseRequest->getKey(),
                'document_uuid' => $workflowContext['document_uuid'],
                'workflow_instance_id' =>
                    $workflowContext['workflow_instance_id'] ?? null,
                'previous_status' => $previousStatus,
                'current_status' => $purchaseRequest->status,
                'changed' => $previousStatus !== $purchaseRequest->status,
            ];
        });
    }

    /**
     * Règles de décision du Document Service.
     *
     * Remplacer les statuts cibles par les valeurs réelles
     * de l'application.
     *
     * @param string $workflowStatus
     * @param string $workflowAction
     * @return string
     */
    private function determineStatus(
        string $workflowStatus,
        string $workflowAction
    ): string {
        switch ($workflowAction) {
            case 'DOCUMENT_VALIDATED':
                if ($workflowStatus !== 'COMPLETE') {
                    throw new InvalidArgumentException(
                        'Une demande ne peut pas être approuvée '
                        . 'si l\'etape n’est pas terminé.'
                    );
                }

                return 'APPROVED';

            case 'DOCUMENT_REJECTED':
                return 'REJECTED';

            case 'DOCUMENT_RETURNED':
                return 'RETURNED';

            default:
                throw new InvalidArgumentException(
                    'Action de workflow non prise en charge : '
                    . $workflowAction
                );
        }
    }
}