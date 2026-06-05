<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseSettlementRequest;
use App\Http\Requests\UpdatePurchaseSettlementRequest;
use App\Models\PurchaseSettlement;

class PurchaseSettlementController extends Controller
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
     * @param  \App\Http\Requests\StorePurchaseSettlementRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchaseSettlementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchaseSettlement  $purchaseSettlement
     * @return \Illuminate\Http\Response
     */
    public function show(PurchaseSettlement $purchaseSettlement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchaseSettlement  $purchaseSettlement
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchaseSettlement $purchaseSettlement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePurchaseSettlementRequest  $request
     * @param  \App\Models\PurchaseSettlement  $purchaseSettlement
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePurchaseSettlementRequest $request, PurchaseSettlement $purchaseSettlement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseSettlement  $purchaseSettlement
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseSettlement $purchaseSettlement)
    {
        //
    }
}
