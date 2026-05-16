<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionPolicyRequest;
use App\Http\Requests\UpdateMissionPolicyRequest;
use App\Models\MissionPolicy;

class MissionPolicyController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionPolicyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionPolicyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionPolicy  $missionPolicy
     * @return \Illuminate\Http\Response
     */
    public function show(MissionPolicy $missionPolicy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionPolicy  $missionPolicy
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionPolicy $missionPolicy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionPolicyRequest  $request
     * @param  \App\Models\MissionPolicy  $missionPolicy
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionPolicyRequest $request, MissionPolicy $missionPolicy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionPolicy  $missionPolicy
     * @return \Illuminate\Http\Response
     */
    public function destroy(MissionPolicy $missionPolicy)
    {
        //
    }
}
