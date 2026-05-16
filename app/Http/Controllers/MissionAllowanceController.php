<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionAllowanceRequest;
use App\Http\Requests\UpdateMissionAllowanceRequest;
use App\Models\Misc\Document;
use App\Models\Mission;
use App\Models\MissionAllowance;
use App\Models\MissionPolicy;
use App\Services\Mission\MissionAllowanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MissionAllowanceController extends Controller
{

    protected MissionAllowanceCalculator $mission_allowance_calculator;

    public function __construct(MissionAllowanceCalculator $mission_allowance_calculator) {
        $this->mission_allowance_calculator = $mission_allowance_calculator;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request , Document $document)
    {
        $token = $request->bearerToken() ;
        $userServiceUrl = config("services.user_service.base_url");

        
        $document->load(['mission']);

        $mission =  $document -> mission;

        $userResponse = Http::withToken($token)
        ->acceptJson()
        ->timeout(5)
        ->get("$userServiceUrl/employees/{$mission -> actor_id}");

        if ($userResponse->ok()) {

      return  $userData = $userResponse;

        // Attachement sans persistance DB
    

        
    } else {
        // log silencieux (important en prod)
        Log::warning("User service failed", [
            'status' => $userResponse->status(),
            'body' => $userResponse->body(),
        ]);
    }

        $policies = MissionPolicy::where('is_active', true)->get();

        $allowances = $this -> mission_allowance_calculator -> calculate( $mission->toArray() , $policies->toArray() );

        return response()->json([
            'success' => true,
            'allowances' => $allowances
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
