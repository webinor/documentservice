<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLedgerCodeTypeRequest;
use App\Http\Requests\UpdateLedgerCodeTypeRequest;
use App\Models\LedgerCodeType;

class LedgerCodeTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $ledgerCodeTypes = LedgerCodeType::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $ledgerCodeTypes
        ]);
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
     * @param  \App\Http\Requests\StoreLedgerCodeTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLedgerCodeTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LedgerCodeType  $ledgerCodeType
     * @return \Illuminate\Http\Response
     */
    public function show(LedgerCodeType $ledgerCodeType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LedgerCodeType  $ledgerCodeType
     * @return \Illuminate\Http\Response
     */
    public function edit(LedgerCodeType $ledgerCodeType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLedgerCodeTypeRequest  $request
     * @param  \App\Models\LedgerCodeType  $ledgerCodeType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLedgerCodeTypeRequest $request, LedgerCodeType $ledgerCodeType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LedgerCodeType  $ledgerCodeType
     * @return \Illuminate\Http\Response
     */
    public function destroy(LedgerCodeType $ledgerCodeType)
    {
        //
    }
}
