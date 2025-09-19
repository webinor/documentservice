<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLedgerCodeRequest;
use App\Http\Requests\UpdateLedgerCodeRequest;
use App\Models\LedgerCode;

class LedgerCodeController extends Controller
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
     * @param  \App\Http\Requests\StoreLedgerCodeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLedgerCodeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LedgerCode  $ledgerCode
     * @return \Illuminate\Http\Response
     */
    public function show(LedgerCode $ledgerCode)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LedgerCode  $ledgerCode
     * @return \Illuminate\Http\Response
     */
    public function edit(LedgerCode $ledgerCode)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLedgerCodeRequest  $request
     * @param  \App\Models\LedgerCode  $ledgerCode
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLedgerCodeRequest $request, LedgerCode $ledgerCode)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LedgerCode  $ledgerCode
     * @return \Illuminate\Http\Response
     */
    public function destroy(LedgerCode $ledgerCode)
    {
        //
    }
}
