<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestOrderRequest;
use App\Http\Requests\UpdateRequestOrderRequest;
use App\Models\PaymentTerm;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierQuote;
use App\Models\RequestOrder;
use App\Services\RequestOrderPaymentScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

 /**
 * Récupère la configuration de paiement d'un bon de commande.
 *
 * @param  \App\Models\RequestOrder  $purchaseOrder
 * @return \Illuminate\Http\JsonResponse
 */
public function OldpaymentConfiguration(
    RequestOrder $purchaseOrder
) {
    return response()->json([
        'success' => true,
        'data' => [
            'payment_mode' => $purchaseOrder->payment_mode,

            'payment_agent_employee_id' =>
                $purchaseOrder->payment_agent_employee_id,
        ],
    ]);
}


/**
 * Récupère la configuration de paiement d'un bon de commande.
 *
 * @param RequestOrder $purchaseOrder
 * @return \Illuminate\Http\JsonResponse
 */
public function paymentConfiguration(RequestOrder $purchaseOrder)
{
    // Récupérer les types de déclencheurs utilisés
    // dans l'échéancier du bon de commande.
    $triggerTypes = $purchaseOrder->paymentSchedules()
        ->pluck('trigger_type')
        ->filter()
        ->unique()
        ->values();

            

    // Récupérer les modes autorisés pour ces déclencheurs.
    $allowedModes = DB::table('payment_trigger_modes')
        ->join(
            'payment_triggers',
            'payment_triggers.id',
            '=',
            'payment_trigger_modes.payment_trigger_id'
        )
        ->whereIn('payment_triggers.code', $triggerTypes)
        ->where('payment_triggers.is_active', true)
        ->distinct()
        ->pluck('payment_trigger_modes.payment_mode')
        ->values();

    return response()->json([
        'success' => true,
        'data' => [
            'payment_mode' => $purchaseOrder->payment_mode,

            'payment_agent_employee_id' =>
                $purchaseOrder->payment_agent_employee_id,

            'allowed_payment_modes' => $allowedModes,
        ],
    ]);
}

  

public function updatePaymentMode(
    Request $request,
    RequestOrder $purchaseOrder
) {
    $validated = $request->validate([
        'payment_mode' => [
            'required',
            Rule::in([
                'DIRECT_SUPPLIER',
                'ASSIGNED_AGENT',
            ]),
        ],

        'payment_agent_employee_id' => [
            'nullable',
            'integer',
            'required_if:payment_mode,ASSIGNED_AGENT',
        ],
    ]);

    if ($validated['payment_mode'] === 'DIRECT_SUPPLIER') {
        $validated['payment_agent_employee_id'] = null;
    }

    /*
     * TODO : lorsque ASSIGNED_AGENT est choisi,
     * vérifier auprès du User/Employee Service que
     * l'employé existe et peut effectuer le paiement.
     */

    $purchaseOrder->update([
        'payment_mode' => $validated['payment_mode'],
        'payment_agent_employee_id' => $validated['payment_agent_employee_id'] ?? null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Mode de paiement enregistré.',
        'data' => [
            'payment_mode' => $purchaseOrder->payment_mode,
            'payment_agent_employee_id' =>
                $purchaseOrder->payment_agent_employee_id,
        ],
    ]);
}

    /**
 * Récupère le bon de commande associé à un devis fournisseur.
 *
 * @param int $supplierQuoteId
 * @return JsonResponse
 */
public function showBySupplierQuote($supplierQuoteId)
{
    $requestOrder = RequestOrder::with('items')
        ->where('supplier_quote_id', $supplierQuoteId)
        ->latest('id')
        ->first();

    if (!$requestOrder) {
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Aucun bon de commande associé à ce devis.',
        ]);
    }

    return response()->json([
        'success' => true,
        'data' => $requestOrder,
    ]);
}

/**
 * Génère les échéances de paiement d'un bon de commande
 * à partir d'une condition de paiement du catalogue.
 *
 * @param RequestOrder $purchaseOrder
 * @param int $paymentTermId
 * @return void
 */
private function createPaymentSchedules(
    RequestOrder $purchaseOrder,
    $paymentTermId
) {
    $paymentTerm = PaymentTerm::with('installments')
        ->findOrFail($paymentTermId);

    $now = now();

    foreach ($paymentTerm->installments as $installment) {
        $amount = (
            $purchaseOrder->total_amount
            * $installment->percentage
        ) / 100;

        $purchaseOrder->paymentSchedules()->create([
            'payment_term_installment_id' => $installment->id,
            'sequence' => $installment->sequence,
            'label' => $installment->label,
            'trigger_type' => $installment->trigger_type,
            'days_after_trigger' => $installment->days_after_trigger,
            'percentage' => $installment->percentage,
            'amount' => $amount,
            'status' => 'PENDING',
        ]);
    }
}

public function store(StoreRequestOrderRequest $request, RequestOrderPaymentScheduleService $requestOrderPaymentScheduleService)
{
    $validated = $request->validated();

    try {
        $purchaseOrder = DB::transaction(function () use ($validated, $requestOrderPaymentScheduleService) {

            // Récupération de la demande d'achat
            $purchaseRequest = PurchaseRequest::findOrFail(
                $validated['purchase_request_id']
            );

            // Récupération du devis sélectionné
            $quote = PurchaseSupplierQuote::where('id', $validated['supplier_quote_id'])
                ->where('purchase_request_id', $purchaseRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Vérification de la sélection du devis
            if (!$quote->is_selected) {
                throw new \Exception(
                    'Le devis fournisseur doit être sélectionné avant de créer le bon de commande.'
                );
            }

            // Vérifier si un BC existe déjà pour ce devis
            $existingOrder = RequestOrder::where(
                'supplier_quote_id',
                $quote->id
            )->first();

            if ($existingOrder) {
                return $existingOrder->load([
                    'items',
                    'paymentInstallments',
                ]);
            }

            // Création du bon de commande
            $purchaseOrder = RequestOrder::create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_quote_id' => $quote->id,
                'supplier_id' => $quote->supplier_id,
                'reference' => null,
                'order_date' => now()->toDateString(),
                'total_amount' => $quote->amount,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);

          $requestOrderPaymentScheduleService
        ->createFromQuote($purchaseOrder, $quote);

            

            // Chargement des relations
            return $purchaseOrder->load([
                'items',
                'paymentSchedules',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Bon de commande créé avec succès.',
            'data' => $purchaseOrder,
        ], 201);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RequestOrder  $requestOrder
     * @return \Illuminate\Http\Response
     */
    public function show(RequestOrder $requestOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RequestOrder  $requestOrder
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestOrder $requestOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRequestOrderRequest  $request
     * @param  \App\Models\RequestOrder  $requestOrder
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequestOrderRequest $request, RequestOrder $requestOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RequestOrder  $requestOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestOrder $requestOrder)
    {
        //
    }
}
