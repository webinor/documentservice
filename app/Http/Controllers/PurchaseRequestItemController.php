<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestItemRequest;
use App\Http\Requests\UpdatePurchaseRequestItemRequest;
use App\Models\PurchaseRequestItem;

class PurchaseRequestItemController extends Controller
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
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePurchaseRequestItemRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseRequestItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseRequestItem  $purchaseRequestItem
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseRequestItem $purchaseRequestItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchaseRequestItem  $purchaseRequestItem
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchaseRequestItem $purchaseRequestItem)
    {
        //
    }

  
/**
 * Met à jour un article d'une demande d'achat.
 *
 * @param  \App\Http\Requests\UpdatePurchaseRequestItemRequest  $request
 * @param  \App\Models\PurchaseRequestItem  $purchaseRequestItem
 * @return \Illuminate\Http\JsonResponse
 */

public function update(
    UpdatePurchaseRequestItemRequest $request,
    $doc_uuid,
    PurchaseRequestItem $item
) {
    $validated = $request->validated();

    if (empty($validated)) {
        return response()->json([
            'success' => false,
            'message' => 'Aucune donnée à mettre à jour.',
        ], 422);
    }

    // Convertir les valeurs numériques égales à 0 en null.
    foreach (['approved_quantity', 'approved_amount'] as $field) {
        if (
            array_key_exists($field, $validated)
            && $validated[$field] !== null
            && is_numeric($validated[$field])
            && (float) $validated[$field] === 0.0
        ) {
            $validated[$field] = null;
        }
    }

    $item->fill($validated);
    $item->save();

    return response()->json([
        'success' => true,
        'message' => 'L’article de la demande d’achat a été mis à jour avec succès.',
        'data' => $item->fresh(),
    ]);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseRequestItem  $purchaseRequestItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseRequestItem $purchaseRequestItem)
    {
        //
    }
}
