<?php

namespace App\Services\PaymentTerms;

use Illuminate\Validation\ValidationException;

class PaymentInstallmentValidator
{
    /**
     * Déclencheurs autorisés.
     */
    public function allowedTriggers()
    {
        return [
            'ORDER_APPROVAL',
            'ORDER_SIGNATURE',
            'ADVANCE_PAYMENT',
            'DELIVERY',
            'PARTIAL_DELIVERY',
            'RECEPTION',
            'INSTALLATION',
            'SERVICE_COMPLETION',
            'ACCEPTANCE',
            'INVOICE_RECEIPT',
            'FIXED_DELAY',
        ];
    }

    /**
     * Vérifie la cohérence des échéances.
     *
     * @param array $installments
     * @return array
     */
    public function validate(array $installments)
    {
        if (count($installments) < 1) {
            throw ValidationException::withMessages([
                'installments' => [
                    'Au moins une échéance est obligatoire.',
                ],
            ]);
        }

        $total = 0;

        foreach ($installments as $index => $installment) {
            $percentage = (float) $installment['percentage'];

            if ($percentage <= 0 || $percentage > 100) {
                throw ValidationException::withMessages([
                    "installments.{$index}.percentage" => [
                        'Le pourcentage doit être supérieur à 0 et inférieur ou égal à 100.',
                    ],
                ]);
            }

            if (!in_array(
                $installment['trigger_type'],
                $this->allowedTriggers(),
                true
            )) {
                throw ValidationException::withMessages([
                    "installments.{$index}.trigger_type" => [
                        'Le déclencheur sélectionné est invalide.',
                    ],
                ]);
            }

            $total += $percentage;
        }

        /*
         * Tolérance pour les calculs décimaux.
         */
        if (abs(round($total, 2) - 100) > 0.001) {
            throw ValidationException::withMessages([
                'installments' => [
                    'La somme des pourcentages des échéances doit être exactement égale à 100 %. Total actuel : '
                    . number_format($total, 2, '.', '')
                    . ' %.',
                ],
            ]);
        }

        return $installments;
    }
}