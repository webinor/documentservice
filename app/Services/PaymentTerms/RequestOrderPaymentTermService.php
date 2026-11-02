<?php

namespace App\Services\PaymentTerms;

use App\Models\PurchaseSupplierQuote;
use App\Models\RequestOrder;
use Illuminate\Validation\ValidationException;

class RequestOrderPaymentTermService
{
    /**
     * Transfère les conditions du devis retenu au bon de commande.
     *
     * @param RequestOrder $order
     * @param PurchaseSupplierQuote $quote
     * @return RequestOrder
     */
    public function apply(
        RequestOrder $order,
        PurchaseSupplierQuote $quote
    ) {
        $quote->load('paymentInstallments', 'paymentTerm');

        if ($quote->paymentInstallments->isEmpty()) {
            throw ValidationException::withMessages([
                'payment_terms' => [
                    'Le devis retenu ne possède aucune condition de paiement.',
                ],
            ]);
        }

        $installments = $quote->paymentInstallments
            ->map(function ($installment) {
                return [
                    'sequence' => $installment->sequence,
                    'label' => $installment->label,
                    'trigger_type' => $installment->trigger_type,
                    'days_after_trigger' => $installment->days_after_trigger,
                    'percentage' => (float) $installment->percentage,
                ];
            })
            ->values()
            ->toArray();

        $order->payment_term_id = $quote->payment_term_id;

        $order->payment_term_code_snapshot =
            $quote->paymentTerm->code ?? null;

        $order->payment_term_name_snapshot =
            $quote->paymentTerm->name ?? 'Condition personnalisée';

        $order->payment_term_description_snapshot =
            $quote->paymentTerm->description ?? null;

        $order->payment_mode_snapshot =
            $quote->paymentTerm->payment_mode ?? 'CUSTOM';

        $order->payment_due_days_snapshot =
            $quote->paymentTerm->due_days ?? null;

        $order->has_custom_payment_terms_snapshot =
            (bool) $quote->has_custom_payment_terms;

        $order->payment_installments_snapshot = $installments;

        $order->save();

        return $order;
    }
}