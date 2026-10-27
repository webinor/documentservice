<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PurchaseRequestApprovalReadinessService
{
    /**
     * Vérifie si une demande d'achat est prête à être validée.
     *
     * La demande est identifiée par le document_id.
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

        foreach ($items as $item) {
            $itemIsValid = true;

            $requestedAmount = $item->requested_amount !== null
                ? (float) $item->requested_amount
                : null;

            $approvedAmount = $item->approved_amount !== null
                ? (float) $item->approved_amount
                : null;

            $approvedQuantity = $item->approved_quantity !== null
                ? (float) $item->approved_quantity
                : null;

            if ($requestedAmount !== null) {
                $requestedAmountTotal += $requestedAmount;
            }

            if ($approvedAmount !== null) {
                $approvedAmountTotal += $approvedAmount;
            }

            /*
             * Le montant approuvé est prioritaire.
             * Si aucun montant approuvé n'existe, on retient
             * le montant demandé pour le récapitulatif financier.
             */
            if ($approvedAmount !== null) {
                $effectiveAmountTotal += $approvedAmount;
            } elseif ($requestedAmount !== null) {
                $effectiveAmountTotal += $requestedAmount;
            }

            /*
             * Vérifier la quantité approuvée.
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
                        'La quantité approuvée doit être renseignée '
                        . 'et supérieure à zéro.',
                ];

                $itemIsValid = false;
            }

            /*
             * Vérifier le montant approuvé.
             * Un montant égal à zéro est accepté.
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
                        'Le montant approuvé doit être renseigné '
                        . 'et supérieur ou égal à zéro.',
                ];

                $itemIsValid = false;
            }

            if ($itemIsValid) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }

        /*
         * Une demande sans article ne peut pas être validée.
         */
        $hasItems = $items->isNotEmpty();

        return [
            'purchase_request_id' => $purchaseRequest->id,
            'document_id' => $documentId,
            'status' => $purchaseRequest->status,

            'is_ready' => $hasItems && $invalidCount === 0,

            'items' => [
                'count' => $items->count(),
                'valid_count' => $validCount,
                'invalid_count' => $invalidCount,
                'requested_amount' => round($requestedAmountTotal, 2),
                'approved_amount' => round($approvedAmountTotal, 2),
                'amount' => round($effectiveAmountTotal, 2),
            ],

            'issues' => $issues,
        ];
    }
}