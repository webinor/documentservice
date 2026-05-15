<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentTypeStatusRequest;
use App\Http\Requests\UpdateDocumentTypeStatusRequest;
use App\Models\DocumentTypeStatus;

class DocumentTypeStatusController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentTypeStatusRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentTypeStatusRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DocumentTypeStatus  $documentTypeStatus
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentTypeStatus $documentTypeStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DocumentTypeStatus  $documentTypeStatus
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentTypeStatus $documentTypeStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentTypeStatusRequest  $request
     * @param  \App\Models\DocumentTypeStatus  $documentTypeStatus
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentTypeStatusRequest $request, DocumentTypeStatus $documentTypeStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DocumentTypeStatus  $documentTypeStatus
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentTypeStatus $documentTypeStatus)
    {
        //
    }
}
