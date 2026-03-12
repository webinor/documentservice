<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGeneralProviderRequest;
use App\Http\Requests\UpdateGeneralProviderRequest;
use App\Models\GeneralProvider;

class GeneralProviderController extends Controller
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
     * @param  \App\Http\Requests\StoreGeneralProviderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreGeneralProviderRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GeneralProvider  $generalProvider
     * @return \Illuminate\Http\Response
     */
    public function show(GeneralProvider $generalProvider)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GeneralProvider  $generalProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(GeneralProvider $generalProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGeneralProviderRequest  $request
     * @param  \App\Models\GeneralProvider  $generalProvider
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGeneralProviderRequest $request, GeneralProvider $generalProvider)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GeneralProvider  $generalProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(GeneralProvider $generalProvider)
    {
        //
    }
}
