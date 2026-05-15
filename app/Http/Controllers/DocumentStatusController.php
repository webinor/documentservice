<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentStatusRequest;
use App\Http\Requests\UpdateDocumentStatusRequest;
use App\Models\DocumentStatus;

class DocumentStatusController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentStatusRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DocumentStatus  $documentStatus
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentStatus $documentStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DocumentStatus  $documentStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentStatus $documentStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentStatusRequest  $request
     * @param  \App\Models\DocumentStatus  $documentStatus
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentStatusRequest $request, DocumentStatus $documentStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DocumentStatus  $documentStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentStatus $documentStatus)
    {
        //
    }
}
