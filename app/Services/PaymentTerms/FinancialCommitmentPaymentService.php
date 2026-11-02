<?php

namespace App\Services\PaymentTerms;

use App\Models\FinancialCommitment;
use App\Models\RequestOrder;
use Illuminate\Validation\ValidationException;

class FinancialCommitmentPaymentService
{
    /**
     * Crée les échéances financières à partir du bon de commande.
     *
     * @param FinancialCommitment $commitment
     * @param RequestOrder $order
     * @return FinancialCommitment
     */
    public function createInstallments(
        FinancialCommitment $commitment,
        RequestOrder $order
    ) {
        $installments = $order->payment_installments_snapshot;

        if (empty($installments) || !is_array($installments)) {
            throw ValidationException::withMessages([
                'payment_terms' => [
                    'Aucune échéance de paiement définie sur le bon de commande.',
                ],
            ]);
        }

        $totalPercentage = 0;

        foreach ($installments as $installment) {
            $totalPercentage += (float) $installment['percentage'];
        }

        if (abs(round($totalPercentage, 2) - 100) > 0.001) {
            throw ValidationException::withMessages([
                'payment_terms' => [
                    'Les échéances du bon de commande ne totalisent pas 100 %.',
                ],
            ]);
        }

        /*
         * Le montant de référence est le montant approuvé,
         * à défaut le montant initial de l'engagement.
         */
        $amount = $commitment->approved_amount !== null
            ? (float) $commitment->approved_amount
            : (float) $commitment->initial_amount;

        $commitment->installments()->delete();

        $createdInstallments = [];
        $allocatedAmount = 0;

        foreach ($installments as $index => $installment) {
            $percentage = (float) $installment['percentage'];

            /*
             * On calcule le montant de chaque tranche.
             * La dernière tranche récupère le reliquat pour
             * éviter les écarts d'arrondi.
             */
            if ($index === count($installments) - 1) {
                $expectedAmount = round(
                    $amount - $allocatedAmount,
                    2
                );
            } else {
                $expectedAmount = round(
                    $amount * $percentage / 100,
                    2
                );
            }

            $allocatedAmount += $expectedAmount;

            $createdInstallments[] = [
                'sequence' => $installment['sequence'],
                'label' => $installment['label'],
                'trigger_type' => $installment['trigger_type'],
                'days_after_trigger' => $installment['days_after_trigger'],
                'percentage' => $percentage,
                'expected_amount' => $expectedAmount,
                'status' => 'PENDING',
            ];
        }

        $commitment->installments()->createMany(
            $createdInstallments
        );

        return $commitment->load('installments');
    }
}