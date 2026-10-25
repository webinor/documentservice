<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\UpdatePurchaseRequestRequest;
use App\Models\Misc\Document;
use App\Models\PurchaseRequest;

class PurchaseRequestController extends Controller
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
     * @param  \App\Http\Requests\StorePurchaseRequestRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseRequestRequest $request)
    {
        //
    }

    // /**
    //  * Display the specified resource.
    //  *
    //  * @param  \App\Models\PurchaseRequest  $purchaseRequest
    //  * @return \Illuminate\Http\Response
    //  */
    // public function show(PurchaseRequest $purchaseRequest)
    // {
    //     //
    // }
    /**
     * Récupère la demande d'achat associée au document.
     *
     * @param  int|string  $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($document)
    {
        $documentModel = Document::where('uuid', $document)->with('purchase_request.purchase_request_items')->first();

        if (!$documentModel) {
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable.',
            ], 404);
        }

        // À adapter selon la relation réelle entre Document
        // et purchase_request dans ton application.
        $purchase_request = $documentModel->purchase_request;

        if (!$purchase_request) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune demande d’achat associée à ce document.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $purchase_request,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchaseRequest  $purchaseRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        //
    }

/**
 * Met à jour une demande d'achat.
 *
 * @param UpdatePurchaseRequestRequest $request
 * @param string $document
 * @param PurchaseRequest $purchaseRequest
 * @return \Illuminate\Http\JsonResponse
 */
public function update(
    UpdatePurchaseRequestRequest $request,
    string $document,
    PurchaseRequest $purchaseRequest
) {
    $validated = $request->validated();

    // Pour un achat interne, aucun client ne doit être associé.
    if (isset($validated['type']) && $validated['type'] === 'INTERNAL') {
        $validated['client_id'] = null;
    }

    // Mettre à jour uniquement les champs autorisés et validés.
    $purchaseRequest->fill($validated);
    $purchaseRequest->save();

    return response()->json([
        'message' => 'La demande d’achat a été mise à jour avec succès.',
        'data' => $purchaseRequest->fresh(),
    ]);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseRequest  $purchaseRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        //
    }
}
