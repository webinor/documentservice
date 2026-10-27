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
     * Lorsqu'une demande est approuvée, les valeurs demandées
     * sont reportées dans les champs approuvés qui sont NULL.
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
            trim((string) ($workflowContext['workflow_instance_step_status'] ?? ''))
        );

        $workflowAction = strtoupper(
            trim((string) ($workflowContext['workflow_action'] ?? ''))
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
             * Mettre à jour le statut uniquement s'il a changé.
             */
            if ($previousStatus !== $newStatus) {
                $purchaseRequest->status = $newStatus;
                $purchaseRequest->save();
            }

            /*
             * Lors de l'approbation, reporter les valeurs demandées
             * dans les champs approuvés qui sont encore NULL.
             *
             * Les valeurs déjà approuvées sont conservées.
             */
            $itemsUpdated = 0;

            if ($newStatus === 'APPROVED') {
                $itemsUpdated = $this->completeApprovedItemValues(
                    $purchaseRequest->getKey()
                );
            }

            return [
                'purchase_request_id' => $purchaseRequest->getKey(),
                'document_uuid' => $workflowContext['document_uuid'] ?? null,
                'workflow_instance_id' =>
                    $workflowContext['workflow_instance_id'] ?? null,
                'previous_status' => $previousStatus,
                'current_status' => $purchaseRequest->status,
                'changed' => $previousStatus !== $purchaseRequest->status,
                'items_updated' => $itemsUpdated,
            ];
        });
    }

    /**
     * Complète les valeurs approuvées des articles.
     *
     * Règles :
     * - Si approved_quantity est NULL, reprendre requested_quantity.
     * - Si approved_amount est NULL, reprendre requested_amount.
     * - Ne jamais écraser une valeur approuvée déjà renseignée.
     *
     * Les deux champs sont traités indépendamment.
     *
     * @param int $purchaseRequestId
     * @return int Nombre d'articles modifiés
     */
    private function completeApprovedItemValues(
        int $purchaseRequestId
    ): int {
        /*
         * Verrouiller les articles pendant leur modification.
         */
        $items = DB::table('purchase_request_items')
            ->where('purchase_request_id', $purchaseRequestId)
            ->where(function ($query) {
                $query->whereNull('approved_quantity')
                    ->orWhereNull('approved_amount');
            })
            ->lockForUpdate()
            ->get();

        $itemsUpdated = 0;

        foreach ($items as $item) {
            $updates = [];

            /*
             * Reporter la quantité demandée uniquement si
             * la quantité approuvée est NULL.
             */
            if ($item->approved_quantity === null) {
                $updates['approved_quantity'] = $item->requested_quantity;
            }

            /*
             * Reporter le montant demandé uniquement si
             * le montant approuvé est NULL.
             */
            if ($item->approved_amount === null) {
                $updates['approved_amount'] = $item->requested_amount;
            }

            /*
             * Ne pas exécuter de mise à jour inutile.
             */
            if (!empty($updates)) {
                DB::table('purchase_request_items')
                    ->where('id', $item->id)
                    ->update($updates);

                $itemsUpdated++;
            }
        }

        return $itemsUpdated;
    }

    /**
     * Règles de décision du Document Service.
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
                        . 'si l\'étape n\'est pas terminée.'
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