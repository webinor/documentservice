<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalProviderRequest;
use App\Http\Requests\UpdateMedicalProviderRequest;
use App\Models\MedicalProvider;

class MedicalProviderController extends Controller
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
     * @param  \App\Http\Requests\StoreMedicalProviderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMedicalProviderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MedicalProvider  $medicalProvider
     * @return \Illuminate\Http\Response
     */
    public function show(MedicalProvider $medicalProvider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MedicalProvider  $medicalProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(MedicalProvider $medicalProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMedicalProviderRequest  $request
     * @param  \App\Models\MedicalProvider  $medicalProvider
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMedicalProviderRequest $request, MedicalProvider $medicalProvider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MedicalProvider  $medicalProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(MedicalProvider $medicalProvider)
    {
        //
    }
}
