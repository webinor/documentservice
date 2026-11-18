<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentTriggerModeRequest;
use App\Http\Requests\UpdatePaymentTriggerModeRequest;
use App\Models\PaymentTriggerMode;

class PaymentTriggerModeController extends Controller
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
     * @param  \App\Http\Requests\StorePaymentTriggerModeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePaymentTriggerModeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PaymentTriggerMode  $paymentTriggerMode
     * @return \Illuminate\Http\Response
     */
    public function show(PaymentTriggerMode $paymentTriggerMode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PaymentTriggerMode  $paymentTriggerMode
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentTriggerMode $paymentTriggerMode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaymentTriggerModeRequest  $request
     * @param  \App\Models\PaymentTriggerMode  $paymentTriggerMode
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePaymentTriggerModeRequest $request, PaymentTriggerMode $paymentTriggerMode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentTriggerMode  $paymentTriggerMode
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentTriggerMode $paymentTriggerMode)
    {
        //
    }
}
