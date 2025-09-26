<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItProviderRequest;
use App\Http\Requests\UpdateItProviderRequest;
use App\Models\ItProvider;

class ItProviderController extends Controller
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
     * @param  \App\Http\Requests\StoreItProviderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreItProviderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ItProvider  $itProvider
     * @return \Illuminate\Http\Response
     */
    public function show(ItProvider $itProvider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ItProvider  $itProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(ItProvider $itProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateItProviderRequest  $request
     * @param  \App\Models\ItProvider  $itProvider
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateItProviderRequest $request, ItProvider $itProvider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ItProvider  $itProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItProvider $itProvider)
    {
        //
    }
}
