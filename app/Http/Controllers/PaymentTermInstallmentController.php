<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentTermInstallmentRequest;
use App\Http\Requests\UpdatePaymentTermInstallmentRequest;
use App\Models\PaymentTermInstallment;

class PaymentTermInstallmentController extends Controller
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
     * @param  \App\Http\Requests\StorePaymentTermInstallmentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePaymentTermInstallmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaymentTermInstallment  $paymentTermInstallment
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentTermInstallment $paymentTermInstallment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PaymentTermInstallment  $paymentTermInstallment
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentTermInstallment $paymentTermInstallment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaymentTermInstallmentRequest  $request
     * @param  \App\Models\PaymentTermInstallment  $paymentTermInstallment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePaymentTermInstallmentRequest $request, PaymentTermInstallment $paymentTermInstallment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentTermInstallment  $paymentTermInstallment
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentTermInstallment $paymentTermInstallment)
    {
        //
    }
}
