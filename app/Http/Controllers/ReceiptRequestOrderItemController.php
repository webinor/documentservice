<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReceiptRequestOrderItemRequest;
use App\Http\Requests\UpdateReceiptRequestOrderItemRequest;
use App\Models\ReceiptRequestOrderItem;

class ReceiptRequestOrderItemController extends Controller
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
     * @param  \App\Http\Requests\StoreReceiptRequestOrderItemRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreReceiptRequestOrderItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReceiptRequestOrderItem  $receiptRequestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function show(ReceiptRequestOrderItem $receiptRequestOrderItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReceiptRequestOrderItem  $receiptRequestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function edit(ReceiptRequestOrderItem $receiptRequestOrderItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateReceiptRequestOrderItemRequest  $request
     * @param  \App\Models\ReceiptRequestOrderItem  $receiptRequestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateReceiptRequestOrderItemRequest $request, ReceiptRequestOrderItem $receiptRequestOrderItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReceiptRequestOrderItem  $receiptRequestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReceiptRequestOrderItem $receiptRequestOrderItem)
    {
        //
    }
}
