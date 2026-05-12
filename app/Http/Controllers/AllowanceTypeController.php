<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAllowanceTypeRequest;
use App\Http\Requests\UpdateAllowanceTypeRequest;
use App\Models\AllowanceType;

class AllowanceTypeController extends Controller
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
     * @param  \App\Http\Requests\StoreAllowanceTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAllowanceTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AllowanceType  $allowanceType
     * @return \Illuminate\Http\Response
     */
    public function show(AllowanceType $allowanceType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AllowanceType  $allowanceType
     * @return \Illuminate\Http\Response
     */
    public function edit(AllowanceType $allowanceType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAllowanceTypeRequest  $request
     * @param  \App\Models\AllowanceType  $allowanceType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAllowanceTypeRequest $request, AllowanceType $allowanceType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AllowanceType  $allowanceType
     * @return \Illuminate\Http\Response
     */
    public function destroy(AllowanceType $allowanceType)
    {
        //
    }
}
