<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormalNoticeRequest;
use App\Http\Requests\UpdateFormalNoticeRequest;
use App\Models\FormalNotice;

class FormalNoticeController extends Controller
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
     * @param  \App\Http\Requests\StoreFormalNoticeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFormalNoticeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FormalNotice  $formalNotice
     * @return \Illuminate\Http\Response
     */
    public function show(FormalNotice $formalNotice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\FormalNotice  $formalNotice
     * @return \Illuminate\Http\Response
     */
    public function edit(FormalNotice $formalNotice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFormalNoticeRequest  $request
     * @param  \App\Models\FormalNotice  $formalNotice
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFormalNoticeRequest $request, FormalNotice $formalNotice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FormalNotice  $formalNotice
     * @return \Illuminate\Http\Response
     */
    public function destroy(FormalNotice $formalNotice)
    {
        //
    }
}
