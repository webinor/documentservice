<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionExpenseReceiptRequest;
use App\Http\Requests\UpdateMissionExpenseReceiptRequest;
use App\Models\MissionExpenseReceipt;

class MissionExpenseReceiptController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionExpenseReceiptRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionExpenseReceiptRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionExpenseReceipt  $missionExpenseReceipt
     * @return \Illuminate\Http\Response
     */
    public function show(MissionExpenseReceipt $missionExpenseReceipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionExpenseReceipt  $missionExpenseReceipt
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionExpenseReceipt $missionExpenseReceipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionExpenseReceiptRequest  $request
     * @param  \App\Models\MissionExpenseReceipt  $missionExpenseReceipt
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionExpenseReceiptRequest $request, MissionExpenseReceipt $missionExpenseReceipt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionExpenseReceipt  $missionExpenseReceipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionExpenseReceipt $missionExpenseReceipt)
    {
        //
    }
}
