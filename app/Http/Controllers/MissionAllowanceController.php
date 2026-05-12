<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionAllowanceRequest;
use App\Http\Requests\UpdateMissionAllowanceRequest;
use App\Models\MissionAllowance;

class MissionAllowanceController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionAllowanceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionAllowanceRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionAllowance  $missionAllowance
     * @return \Illuminate\Http\Response
     */
    public function show(MissionAllowance $missionAllowance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionAllowance  $missionAllowance
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionAllowance $missionAllowance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionAllowanceRequest  $request
     * @param  \App\Models\MissionAllowance  $missionAllowance
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionAllowanceRequest $request, MissionAllowance $missionAllowance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionAllowance  $missionAllowance
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionAllowance $missionAllowance)
    {
        //
    }
}
