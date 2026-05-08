<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMissionExpenseRequest;
use App\Http\Requests\UpdateMissionExpenseRequest;
use App\Models\Misc\Document;
use App\Models\MissionExpense;

class MissionExpenseController extends Controller
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
     * @param  \App\Http\Requests\StoreMissionExpenseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMissionExpenseRequest $request , Document $document)
    {

         $document->load(['mission']);
     
    
        // $validated = $request->validate([
        //     'mission_id' => ['required', 'exists:missions,id'],
        //     'expense_category_id' => ['nullable', 'exists:expense_categories,id'],
        //     'amount' => ['nullable', 'numeric'],
        //     'expense_date' => ['nullable', 'date'],
        //     'description' => ['nullable', 'string'],
        // ]);

        $expense = MissionExpense::create([
            'mission_id' => $document->mission->id,
            // 'expense_category_id' => $validated['expense_category_id'] ?? null,
            // 'amount' => $validated['amount'] ?? 0,
            // 'expense_date' => $validated['expense_date'] ?? now(),
            // 'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense,
        ], 201);
    
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MissionExpense  $missionExpense
     * @return \Illuminate\Http\Response
     */
    public function show(MissionExpense $missionExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MissionExpense  $missionExpense
     * @return \Illuminate\Http\Response
     */
    public function edit(MissionExpense $missionExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMissionExpenseRequest  $request
     * @param  \App\Models\MissionExpense  $missionExpense
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMissionExpenseRequest $request, Document $document, MissionExpense $missionExpense)
    {

  
    // return    $missionExpense;
    $validated = $request->validated();

    $missionExpense->update($validated);

    return response()->json([
        'message' => 'Expense updated successfully',
        'data' => $missionExpense->fresh(),
    ]);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MissionExpense  $missionExpense
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document, MissionExpense $missionExpense)
    {
        // return $document;
        // return $missionExpense;
         $missionExpense->delete();

    return response()->json([
        'message' => 'Expense deleted successfully'
    ]);
    }
}
