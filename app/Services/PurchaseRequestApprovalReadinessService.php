<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PurchaseRequestApprovalReadinessService
{
    /**
     * Vérifie si une demande d'achat est prête à être validée.
     *
     * Un article est prêt si :
     * - sa quantité approuvée est renseignée ;
     * - son montant approuvé est renseigné ;
     * - sa checkbox is_ok est cochée.
     *
     * @param int $documentId
     * @return array|null
     */
    public function evaluateByDocumentId(int $documentId): ?array
    {
        $purchaseRequest = DB::table('purchase_requests')
            ->where('document_id', $documentId)
            ->first();

        if (!$purchaseRequest) {
            return null;
        }

        $items = DB::table('purchase_request_items')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->get();

        $issues = [];

        $requestedAmountTotal = 0.0;
        $approvedAmountTotal = 0.0;
        $effectiveAmountTotal = 0.0;

        $validCount = 0;
        $invalidCount = 0;
        $confirmedCount = 0;
        $unconfirmedCount = 0;

        foreach ($items as $item) {
            $itemFieldsAreValid = true;

            $requestedAmount = $item->requested_amount !== null
                ? (float) $item->requested_amount
                : null;

            $approvedAmount = $item->approved_amount !== null
                ? (float) $item->approved_amount
                : null;

            $approvedQuantity = $item->approved_quantity !== null
                ? (float) $item->approved_quantity
                : null;

            $isConfirmed = (bool) $item->is_ok;

            /*
             * =====================================================
             * 1. Calcul des montants
             * =====================================================
             */

            if ($requestedAmount !== null) {
                $requestedAmountTotal += $requestedAmount;
            }

            if ($approvedAmount !== null) {
                $approvedAmountTotal += $approvedAmount;
            }

            if ($approvedAmount !== null) {
                $effectiveAmountTotal += $approvedAmount;
            } elseif ($requestedAmount !== null) {
                $effectiveAmountTotal += $requestedAmount;
            }

            /*
             * =====================================================
             * 2. Vérification de la quantité approuvée
             * =====================================================
             */

            if (
                $approvedQuantity === null ||
                $approvedQuantity < 0
            ) {
                $issues[] = [
                    'item_id' => $item->id,
                    'designation' => $item->designation,
                    'field' => 'approved_quantity',
                    'message' =>
                        'La quantité approuvée de l’article "'
                        . $item->designation
                        . '" doit être renseignée et supérieure ou égale à zéro.',
                ];

                $itemFieldsAreValid = false;
            }

            /*
             * =====================================================
             * 3. Vérification du montant approuvé
             * =====================================================
             */

            if (
                $approvedAmount === null ||
                $approvedAmount < 0
            ) {
                $issues[] = [
                    'item_id' => $item->id,
                    'designation' => $item->designation,
                    'field' => 'approved_amount',
                    'message' =>
                        'Le montant approuvé de l’article "'
                        . $item->designation
                        . '" doit être renseigné et supérieur ou égal à zéro.',
                ];

                $itemFieldsAreValid = false;
            }

            /*
             * =====================================================
             * 4. Comptabilisation des informations
             * =====================================================
             */

            if ($itemFieldsAreValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }

            /*
             * =====================================================
             * 5. Vérification de la confirmation
             * =====================================================
             */

            if ($isConfirmed) {
                $confirmedCount++;
            } else {
                $unconfirmedCount++;

                $issues[] = [
                    'item_id' => $item->id,
                    'designation' => $item->designation,
                    'field' => 'is_ok',
                    'message' =>
                        'L’article "' . $item->designation
                        . '" doit être vérifié et confirmé.',
                ];
            }
        }

        /*
         * =====================================================
         * 6. État global de la demande
         * =====================================================
         */

        $hasItems = $items->isNotEmpty();

        $isReady = $hasItems
            && $invalidCount === 0
            && $unconfirmedCount === 0;

        return [
            'purchase_request_id' => $purchaseRequest->id,
            'document_id' => $documentId,
            'status' => $purchaseRequest->status,

            'is_ready' => $isReady,

            'items' => [
                'count' => $items->count(),

                // Informations quantitatives et financières
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,

                // Confirmation utilisateur
                'confirmed_count' => $confirmedCount,
                'unconfirmed_count' => $unconfirmedCount,

                'requested_amount' => round($requestedAmountTotal, 2),
                'approved_amount' => round($approvedAmountTotal, 2),
                'amount' => round($effectiveAmountTotal, 2),
            ],

            'issues' => $issues,
        ];
    }
}