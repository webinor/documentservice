<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentTypeCategoryRequest;
use App\Http\Requests\UpdateAttachmentTypeCategoryRequest;
use App\Models\AttachmentTypeCategory;

class AttachmentTypeCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $attachmentTypeCategories = AttachmentTypeCategory::orderBy('name')->get();

          return response()->json([
            'success' => true,
            'data' => $attachmentTypeCategories
        ]);
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
     * @param  \App\Http\Requests\StoreAttachmentTypeCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttachmentTypeCategoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AttachmentTypeCategory  $attachmentTypeCategory
     * @return \Illuminate\Http\Response
     */
    public function show(AttachmentTypeCategory $attachmentTypeCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AttachmentTypeCategory  $attachmentTypeCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(AttachmentTypeCategory $attachmentTypeCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAttachmentTypeCategoryRequest  $request
     * @param  \App\Models\AttachmentTypeCategory  $attachmentTypeCategory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttachmentTypeCategoryRequest $request, AttachmentTypeCategory $attachmentTypeCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AttachmentTypeCategory  $attachmentTypeCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(AttachmentTypeCategory $attachmentTypeCategory)
    {
        //
    }
}
