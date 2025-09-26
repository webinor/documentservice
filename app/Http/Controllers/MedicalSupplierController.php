<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalSupplierRequest;
use App\Http\Requests\UpdateMedicalSupplierRequest;
use App\Models\MedicalSupplier;

class MedicalSupplierController extends Controller
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
     * @param  \App\Http\Requests\StoreMedicalSupplierRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMedicalSupplierRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MedicalSupplier  $medicalSupplier
     * @return \Illuminate\Http\Response
     */
    public function show(MedicalSupplier $medicalSupplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MedicalSupplier  $medicalSupplier
     * @return \Illuminate\Http\Response
     */
    public function edit(MedicalSupplier $medicalSupplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMedicalSupplierRequest  $request
     * @param  \App\Models\MedicalSupplier  $medicalSupplier
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMedicalSupplierRequest $request, MedicalSupplier $medicalSupplier)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MedicalSupplier  $medicalSupplier
     * @return \Illuminate\Http\Response
     */
    public function destroy(MedicalSupplier $medicalSupplier)
    {
        //
    }
}
