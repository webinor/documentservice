<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestOrderRequest;
use App\Http\Requests\UpdateRequestOrderRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierQuote;
use App\Models\RequestOrder;
use Illuminate\Support\Facades\DB;

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

public function store(StoreRequestOrderRequest $request)
{
    $validated = $request->validated();

    try {
        $purchaseOrder = DB::transaction(function () use ($validated) {

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

            // Chargement des relations
            return $purchaseOrder->load([
                'items',
                'paymentInstallments',
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
