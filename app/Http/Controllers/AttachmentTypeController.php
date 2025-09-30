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
     * Retourne les attachment_types requis non encore associés au document
     */
    public function missingForDocument(Request $request, $documentId)
    {
        // IDs envoyés depuis le WorkflowService
        $requiredIds = $request->input('attachment_type_required', []);

        if (empty($requiredIds)) {
            return response()->json([]);
        }

        // IDs des attachment_types déjà associés au document
        $existingIds = Attachment::where('document_id', $documentId)
            ->pluck('attachment_type_id')
            ->toArray();

        // IDs requis qui ne sont pas encore associés
        $missingIds = array_diff($requiredIds, $existingIds);

        // Récupérer les informations détaillées pour ces attachment_types
       $missingAttachmentTypes = AttachmentType::whereIn('id', $missingIds)
       ->with("attachmentTypeCategory")
    ->get()
    ->map(function ($type) {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'slug' => $type->slug,
            'slug' => $type->slug,
            'attachment_number_required'=> $type->attachment_number_required,
            'category_id'=> $type->attachmentTypeCategory->id,
            'category_name'=> $type->attachmentTypeCategory->name,
        ];
    });
        return response()->json($missingAttachmentTypes);
        /*  return response()->json([
            "success" => true,
            "data" => ["missingAttachmentTypes" => $missingAttachmentTypes],
        ]);*/
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
       // return $attachmentType;
       // $attachmentType = AttachmentType::find($id);

        if (!$attachmentType) {
            return response()->json([
                'message' => 'Attachment type not found'
            ], 404);
        }

        return response()->json($attachmentType);
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
