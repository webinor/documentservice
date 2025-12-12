<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAbsenceRequestRequest;
use App\Http\Requests\UpdateAbsenceRequestRequest;
use App\Models\AbsenceRequest;

class AbsenceRequestController extends Controller
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
     * @param  \App\Http\Requests\StoreAbsenceRequestRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAbsenceRequestRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AbsenceRequest  $absenceRequest
     * @return \Illuminate\Http\Response
     */
    public function show(AbsenceRequest $absenceRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AbsenceRequest  $absenceRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(AbsenceRequest $absenceRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAbsenceRequestRequest  $request
     * @param  \App\Models\AbsenceRequest  $absenceRequest
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAbsenceRequestRequest $request, AbsenceRequest $absenceRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AbsenceRequest  $absenceRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(AbsenceRequest $absenceRequest)
    {
        //
    }
}
