<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestOrderRequest;
use App\Http\Requests\UpdateRequestOrderRequest;
use App\Models\RequestOrder;

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
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRequestOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequestOrderRequest $request)
    {
        //
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
