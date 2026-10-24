<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialCommitmentRequest;
use App\Http\Requests\UpdateFinancialCommitmentRequest;
use App\Models\FinancialCommitment;

class FinancialCommitmentController extends Controller
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
     * @param  \App\Http\Requests\StoreFinancialCommitmentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFinancialCommitmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FinancialCommitment  $financialCommitment
     * @return \Illuminate\Http\Response
     */
    public function show(FinancialCommitment $financialCommitment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FinancialCommitment  $financialCommitment
     * @return \Illuminate\Http\Response
     */
    public function edit(FinancialCommitment $financialCommitment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFinancialCommitmentRequest  $request
     * @param  \App\Models\FinancialCommitment  $financialCommitment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFinancialCommitmentRequest $request, FinancialCommitment $financialCommitment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FinancialCommitment  $financialCommitment
     * @return \Illuminate\Http\Response
     */
    public function destroy(FinancialCommitment $financialCommitment)
    {
        //
    }
}
