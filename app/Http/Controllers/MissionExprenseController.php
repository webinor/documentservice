<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionExprenseRequest;
use App\Http\Requests\UpdateMissionExprenseRequest;
use App\Models\MissionExprense;

class MissionExprenseController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionExprenseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionExprenseRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionExprense  $missionExprense
     * @return \Illuminate\Http\Response
     */
    public function show(MissionExprense $missionExprense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionExprense  $missionExprense
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionExprense $missionExprense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionExprenseRequest  $request
     * @param  \App\Models\MissionExprense  $missionExprense
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionExprenseRequest $request, MissionExprense $missionExprense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionExprense  $missionExprense
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionExprense $missionExprense)
    {
        //
    }
}
