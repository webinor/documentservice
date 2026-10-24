<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestOrderPaymentScheduleRequest;
use App\Http\Requests\UpdateRequestOrderPaymentScheduleRequest;
use App\Models\RequestOrderPaymentSchedule;

class RequestOrderPaymentScheduleController extends Controller
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
     * @param  \App\Http\Requests\StoreRequestOrderPaymentScheduleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequestOrderPaymentScheduleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function show(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function edit(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRequestOrderPaymentScheduleRequest  $request
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequestOrderPaymentScheduleRequest $request, RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RequestOrderPaymentSchedule  $requestOrderPaymentSchedule
     * @return \Illuminate\Http\Response
     */
    public function destroy(RequestOrderPaymentSchedule $requestOrderPaymentSchedule)
    {
        //
    }
}
