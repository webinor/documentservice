<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentStatusHistoryRequest;
use App\Http\Requests\UpdateDocumentStatusHistoryRequest;
use App\Models\Misc\DocumentStatusHistory;

class DocumentStatusHistoryController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentStatusHistoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentStatusHistoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\DocumentStatusHistory  $documentStatusHistory
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentStatusHistory $documentStatusHistory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Misc\DocumentStatusHistory  $documentStatusHistory
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentStatusHistory $documentStatusHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentStatusHistoryRequest  $request
     * @param  \App\Models\Misc\DocumentStatusHistory  $documentStatusHistory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentStatusHistoryRequest $request, DocumentStatusHistory $documentStatusHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Misc\DocumentStatusHistory  $documentStatusHistory
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentStatusHistory $documentStatusHistory)
    {
        //
    }
}
