<?php

namespace App\Http\Controllers;

use App\Models\PaymentTrigger;
use Illuminate\Http\JsonResponse;

class PaymentTriggerController extends Controller
{
    /**
     * Liste les déclencheurs de paiement actifs.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $triggers = PaymentTrigger::active()
            ->orderBy('sequence')
            ->orderBy('label')
            ->get([
                'id',
                'code',
                'label',
                'description',
                'sequence',
            ]);

        return response()->json([
            'success' => true,
            'data' => $triggers,
        ]);
    }
}