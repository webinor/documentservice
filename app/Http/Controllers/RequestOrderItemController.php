<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestOrderItemRequest;
use App\Http\Requests\UpdateRequestOrderItemRequest;
use App\Models\RequestOrderItem;

class RequestOrderItemController extends Controller
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
     * @param  \App\Http\Requests\StoreRequestOrderItemRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequestOrderItemRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RequestOrderItem  $requestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function show(RequestOrderItem $requestOrderItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RequestOrderItem  $requestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestOrderItem $requestOrderItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRequestOrderItemRequest  $request
     * @param  \App\Models\RequestOrderItem  $requestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequestOrderItemRequest $request, RequestOrderItem $requestOrderItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RequestOrderItem  $requestOrderItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestOrderItem $requestOrderItem)
    {
        //
    }
}
