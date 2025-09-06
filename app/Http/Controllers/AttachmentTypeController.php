<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentTypeRequest;
use App\Http\Requests\UpdateAttachmentTypeRequest;
use App\Models\Misc\AttachmentType;

class AttachmentTypeController extends Controller
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
     * @param  \App\Http\Requests\StoreAttachmentTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttachmentTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\AttachmentType  $attachmentType
     * @return \Illuminate\Http\Response
     */
    public function show(AttachmentType $attachmentType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Misc\AttachmentType  $attachmentType
     * @return \Illuminate\Http\Response
     */
    public function edit(AttachmentType $attachmentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAttachmentTypeRequest  $request
     * @param  \App\Models\Misc\AttachmentType  $attachmentType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttachmentTypeRequest $request, AttachmentType $attachmentType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Misc\AttachmentType  $attachmentType
     * @return \Illuminate\Http\Response
     */
    public function destroy(AttachmentType $attachmentType)
    {
        //
    }
}
