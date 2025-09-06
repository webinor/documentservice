<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceProviderRequest;
use App\Http\Requests\UpdateInvoiceProviderRequest;
use App\Models\Finance\InvoiceProvider;

class InvoiceProviderController extends Controller
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
     * @param  \App\Http\Requests\StoreInvoiceProviderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInvoiceProviderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Finance\InvoiceProvider  $invoiceProvider
     * @return \Illuminate\Http\Response
     */
    public function show(InvoiceProvider $invoiceProvider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Finance\InvoiceProvider  $invoiceProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(InvoiceProvider $invoiceProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInvoiceProviderRequest  $request
     * @param  \App\Models\Finance\InvoiceProvider  $invoiceProvider
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceProviderRequest $request, InvoiceProvider $invoiceProvider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Finance\InvoiceProvider  $invoiceProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvoiceProvider $invoiceProvider)
    {
        //
    }
}
