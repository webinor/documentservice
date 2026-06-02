<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionRegulationRequest;
use App\Http\Requests\UpdateMissionRegulationRequest;
use App\Models\MissionRegulation;

class MissionRegulationController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionRegulationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionRegulationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionRegulation  $missionRegulation
     * @return \Illuminate\Http\Response
     */
    public function show(MissionRegulation $missionRegulation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionRegulation  $missionRegulation
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionRegulation $missionRegulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionRegulationRequest  $request
     * @param  \App\Models\MissionRegulation  $missionRegulation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionRegulationRequest $request, MissionRegulation $missionRegulation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionRegulation  $missionRegulation
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionRegulation $missionRegulation)
    {
        //
    }
}
