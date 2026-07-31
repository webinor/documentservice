<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentVerificationRequest;
use App\Http\Requests\UpdateDocumentVerificationRequest;
use App\Models\DocumentVerification;

class DocumentVerificationController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentVerificationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentVerificationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DocumentVerification  $documentVerification
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentVerification $documentVerification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DocumentVerification  $documentVerification
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentVerification $documentVerification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentVerificationRequest  $request
     * @param  \App\Models\DocumentVerification  $documentVerification
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentVerificationRequest $request, DocumentVerification $documentVerification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DocumentVerification  $documentVerification
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentVerification $documentVerification)
    {
        //
    }
}
