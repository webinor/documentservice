<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentTerm;
use App\Models\PurchaseSupplierQuote;
use App\Services\PaymentTerms\PaymentInstallmentValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseSupplierQuotePaymentTermController extends Controller
{
    protected $installmentValidator;

    public function __construct(
        PaymentInstallmentValidator $installmentValidator
    ) {
        $this->installmentValidator = $installmentValidator;
    }

    /**
     * Consulte les conditions de paiement d'un devis.
     *
     * GET /purchase-supplier-quotes/{quoteId}/payment-terms
     */
    public function show($quoteId)
    {
        $quote = PurchaseSupplierQuote::with([
            'paymentTerm',
            'paymentInstallments',
        ])->findOrFail($quoteId);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_term_id' => $quote->payment_term_id,
                'has_custom_payment_terms' => $quote->has_custom_payment_terms,
                'payment_term' => $quote->paymentTerm,
                'installments' => $quote->paymentInstallments,
            ],
        ]);
    }

    /**
     * Affecte une condition du catalogue à un devis.
     *
     * POST /purchase-supplier-quotes/{quoteId}/payment-terms/catalog
     */
    public function useCatalog(Request $request, $quoteId)
    {
        $validated = $request->validate([
            'payment_term_id' => 'required|integer|exists:payment_terms,id',
        ]);

        $quote = PurchaseSupplierQuote::findOrFail($quoteId);

        $term = PaymentTerm::with('installments')
            ->active()
            ->findOrFail($validated['payment_term_id']);

        if ($term->installments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette condition de paiement ne possède aucune échéance.',
            ], 422);
        }

        $installments = $term->installments
            ->map(function ($installment) {
                return [
                    'sequence' => $installment->sequence,
                    'label' => $installment->label,
                    'trigger_type' => $installment->trigger_type,
                    'days_after_trigger' => $installment->days_after_trigger,
                    'percentage' => $installment->percentage,
                ];
            })
            ->values()
            ->toArray();

        $this->installmentValidator->validate($installments);

        $quote = DB::transaction(function () use ($quote, $term, $installments) {
            /*
             * On remplace les anciennes échéances du devis.
             */
            $quote->paymentInstallments()->delete();

            $quote->paymentInstallments()->createMany($installments);

            $quote->payment_term_id = $term->id;
            $quote->has_custom_payment_terms = false;
            $quote->save();

            return $quote->load([
                'paymentTerm',
                'paymentInstallments',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Condition de paiement du catalogue affectée au devis.',
            'data' => $quote,
        ]);
    }

    /**
     * Définit des conditions de paiement personnalisées.
     *
     * POST /purchase-supplier-quotes/{quoteId}/payment-terms/custom
     */
    public function saveCustom(Request $request, $quoteId)
    {
        $validated = $request->validate([
            'installments' => 'required|array|min:1',

            'installments.*.sequence' => 'required|integer|min:1',
            'installments.*.label' => 'required|string|max:150',

            'installments.*.trigger_type' => [
                'required',
                'string',
                Rule::in($this->installmentValidator->allowedTriggers()),
            ],

            'installments.*.days_after_trigger' => 'nullable|integer|min:0|max:65535',

            'installments.*.percentage' => 'required|numeric|gt:0|lte:100',
        ]);

        $this->installmentValidator->validate(
            $validated['installments']
        );

        $sequences = array_column($validated['installments'], 'sequence');

        if (count($sequences) !== count(array_unique($sequences))) {
            return response()->json([
                'success' => false,
                'message' => 'Les numéros de séquence doivent être uniques.',
            ], 422);
        }

        $quote = PurchaseSupplierQuote::findOrFail($quoteId);

        $quote = DB::transaction(function () use ($quote, $validated) {
            /*
             * Une condition personnalisée n'est rattachée
             * à aucun modèle du catalogue.
             */
            $quote->paymentInstallments()->delete();

            $installments = array_map(function ($installment) {
                return [
                    'sequence' => $installment['sequence'],
                    'label' => $installment['label'],
                    'trigger_type' => $installment['trigger_type'],
                    'days_after_trigger' => $installment['days_after_trigger'] ?? 0,
                    'percentage' => $installment['percentage'],
                ];
            }, $validated['installments']);

            $quote->paymentInstallments()->createMany($installments);

            $quote->payment_term_id = null;
            $quote->has_custom_payment_terms = true;
            $quote->save();

            return $quote->load('paymentInstallments');
        });

        return response()->json([
            'success' => true,
            'message' => 'Conditions de paiement personnalisées enregistrées.',
            'data' => $quote,
        ]);
    }

    /**
     * Supprime les conditions de paiement d'un devis.
     *
     * DELETE /purchase-supplier-quotes/{quoteId}/payment-terms
     */
    public function destroy($quoteId)
    {
        $quote = PurchaseSupplierQuote::findOrFail($quoteId);

        DB::transaction(function () use ($quote) {
            $quote->paymentInstallments()->delete();

            $quote->payment_term_id = null;
            $quote->has_custom_payment_terms = false;
            $quote->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Conditions de paiement supprimées du devis.',
        ]);
    }
}