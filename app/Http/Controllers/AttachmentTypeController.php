<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentTypeRequest;
use App\Http\Requests\UpdateAttachmentTypeRequest;
use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
use App\Models\Misc\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttachmentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     /**
     * Récupère tous les types de pièces jointes
     */
    public function index(Request $request , $category)
    {
        $attachmentTypes = AttachmentType::whereHas("attachmentTypeCategory",function($query) use ($category){


            if (is_numeric($category)) {
                $query->whereId($category);
            } else {
                $query->whereSlug($category);
            }
            
           

        })->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $attachmentTypes
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
     * @param  \App\Http\Requests\StoreAttachmentTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttachmentTypeRequest $request)
    {
        
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
