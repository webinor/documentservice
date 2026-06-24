<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionRequest;
use App\Http\Requests\UpdateMissionRequest;
use App\Models\Misc\Document;
use App\Models\Mission;
use App\Services\Mission\MissionSheetGenerator;

class MissionController extends Controller
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

    public function generateSheet(
        Document $document,
        MissionSheetGenerator $generator
    ) {


    // throw new \Exception(json_encode($document), 1);
    
// return 
 $document->load('mission');

    $mission = $document->mission;
        $filePath = $generator->generate($mission);

    // throw new \Exception(json_encode($filePath), 1);


        return response()->download(
            $filePath
        );//->deleteFileAfterSend(true);
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
     * @param  \App\Http\Requests\StoreMissionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Mission  $mission
     * @return \Illuminate\Http\Response
     */
    public function show(Mission $mission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Mission  $mission
     * @return \Illuminate\Http\Response
     */
    public function edit(Mission $mission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionRequest  $request
     * @param  \App\Models\Mission  $mission
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionRequest $request, Document $document)
{

    //   return  
      $mission = $document->mission;

        if (!$mission) {

            return response()->json([
                'success' => false,
                'message' => 'Mission introuvable'
            ], 404);
        }

        /**
         * ✅ Validation dynamique
         */
        $validated = $request->validated();

        /**
         * ✅ UPDATE
         */
        $mission->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mission mise à jour',
            'data' => $mission->fresh()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Mission  $mission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Mission $mission)
    {
        //
    }
}
