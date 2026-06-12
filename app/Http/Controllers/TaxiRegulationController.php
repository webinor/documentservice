<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxiRegulationRequest;
use App\Http\Requests\UpdateTaxiRegulationRequest;
use App\Models\TaxiRegulation;

class TaxiRegulationController extends Controller
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
     * @param  \App\Http\Requests\StoreTaxiRegulationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaxiRegulationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TaxiRegulation  $taxiRegulation
     * @return \Illuminate\Http\Response
     */
    public function show(TaxiRegulation $taxiRegulation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TaxiRegulation  $taxiRegulation
     * @return \Illuminate\Http\Response
     */
    public function edit(TaxiRegulation $taxiRegulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTaxiRegulationRequest  $request
     * @param  \App\Models\TaxiRegulation  $taxiRegulation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTaxiRegulationRequest $request, TaxiRegulation $taxiRegulation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TaxiRegulation  $taxiRegulation
     * @return \Illuminate\Http\Response
     */
    public function destroy(TaxiRegulation $taxiRegulation)
    {
        //
    }
}
