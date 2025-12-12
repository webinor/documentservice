<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxiPaperRequest;
use App\Http\Requests\UpdateTaxiPaperRequest;
use App\Models\TaxiPaper;

class TaxiPaperController extends Controller
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
     * @param  \App\Http\Requests\StoreTaxiPaperRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaxiPaperRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TaxiPaper  $taxiPaper
     * @return \Illuminate\Http\Response
     */
    public function show(TaxiPaper $taxiPaper)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TaxiPaper  $taxiPaper
     * @return \Illuminate\Http\Response
     */
    public function edit(TaxiPaper $taxiPaper)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTaxiPaperRequest  $request
     * @param  \App\Models\TaxiPaper  $taxiPaper
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateTaxiPaperRequest $request, TaxiPaper $taxiPaper)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TaxiPaper  $taxiPaper
     * @return \Illuminate\Http\Response
     */
    public function destroy(TaxiPaper $taxiPaper)
    {
        //
    }
}
