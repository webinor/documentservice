<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use Illuminate\Http\JsonResponse;

class PaymentTermController extends Controller
{
    /**
     * Retourne les conditions de paiement actives
     * avec leurs échéances, pour les formulaires d'achat.
     *
     * GET /api/payment-terms
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $paymentTerms = PaymentTerm::query()
            ->active()
            ->with([
                'installments' => function ($query) {
                    $query->select([
                        'id',
                        'payment_term_id',
                        'sequence',
                        'label',
                        'trigger_type',
                        'days_after_trigger',
                        'percentage',
                    ])->orderBy('sequence');
                },
            ])
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'description',
                'payment_mode',
                'due_days',
                'is_default',
            ]);

        return response()->json([
            'success' => true,
            'data' => $paymentTerms,
        ]);
    }
}