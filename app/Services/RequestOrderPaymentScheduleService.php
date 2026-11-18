<?php

namespace App\Services;

use App\Models\RequestOrder;
use App\Models\PurchaseSupplierQuote;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RequestOrderPaymentScheduleService
{
    /**
     * Génère les échéances du bon de commande
     * à partir des conditions négociées dans le devis.
     *
     * @param RequestOrder $purchaseOrder
     * @param PurchaseSupplierQuote $quote
     * @return void
     */
    public function createFromQuote(
        RequestOrder $purchaseOrder,
        PurchaseSupplierQuote $quote
    ) {
        $installments = $quote->paymentInstallments;

        if ($installments->isEmpty()) {
            throw new InvalidArgumentException(
                'Aucune échéance de paiement définie dans le devis fournisseur.'
            );
        }

        // Vérifier que la répartition représente exactement 100 %.
        $totalPercentage = $installments->sum('percentage');

        if (bccomp((string) $totalPercentage, '100.00', 2) !== 0) {
            throw new InvalidArgumentException(
                'Le total des pourcentages des échéances doit être égal à 100 %.'
            );
        }

        $totalAmount = (string) $purchaseOrder->total_amount;
        $allocatedAmount = '0.00';

        foreach ($installments as $index => $installment) {
            // Éviter les erreurs d'arrondi en attribuant
            // le reliquat à la dernière échéance.
            if ($index === $installments->count() - 1) {
                $amount = bcsub(
                    $totalAmount,
                    $allocatedAmount,
                    2
                );
            } else {
                $amount = bcdiv(
                    bcmul(
                        $totalAmount,
                        (string) $installment->percentage,
                        4
                    ),
                    '100',
                    2
                );

                $allocatedAmount = bcadd(
                    $allocatedAmount,
                    $amount,
                    2
                );
            }

            $purchaseOrder->paymentSchedules()->create([
                'sequence' => $installment->sequence,
                'label' => $installment->label,
                'trigger_type' => $installment->trigger_type,
                'days_after_trigger' =>
                    $installment->days_after_trigger,
                'percentage' => $installment->percentage,
                'amount' => $amount,
                'status' => 'PLANNED',
            ]);
        }
    }
}