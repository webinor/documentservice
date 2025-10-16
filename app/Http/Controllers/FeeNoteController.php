<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeNoteRequest;
use App\Http\Requests\UpdateFeeNoteRequest;
use App\Models\FeeNote;

class FeeNoteController extends Controller
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
     * @param  \App\Http\Requests\StoreFeeNoteRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFeeNoteRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FeeNote  $feeNote
     * @return \Illuminate\Http\Response
     */
    public function show(FeeNote $feeNote)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FeeNote  $feeNote
     * @return \Illuminate\Http\Response
     */
    public function edit(FeeNote $feeNote)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFeeNoteRequest  $request
     * @param  \App\Models\FeeNote  $feeNote
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFeeNoteRequest $request, FeeNote $feeNote)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FeeNote  $feeNote
     * @return \Illuminate\Http\Response
     */
    public function destroy(FeeNote $feeNote)
    {
        //
    }
}
