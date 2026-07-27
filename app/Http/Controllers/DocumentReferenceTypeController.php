<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentReferenceTypeRequest;
use App\Http\Requests\UpdateDocumentReferenceTypeRequest;
use App\Models\DocumentReferenceType;
use App\Services\Common\WorkflowRequirementService;
use App\Services\Document\DocumentService;
use Exception;
use Illuminate\Http\Request;

class DocumentReferenceTypeController extends Controller
{


public function missingForDocument(
    Request $request,
    DocumentService $documentService,
    WorkflowRequirementService $requirements,
    string $documentUuid
) {
    $document = $documentService->getDoc($documentUuid);

    // throw new Exception(json_encode($request->input('reference_types_required', [])), 1);


    $missing = [
        'attachments' => $requirements->getMissingAttachments(
            $document->id,
            $request->input('attachment_type_required', [])
        ),

        'references' => $requirements->getMissingReferences(
            $document->id,
            $request->input('reference_types_required', [])
        ),
    ];

    return response()->json($missing);
}
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
     * @param  \App\Http\Requests\StoreDocumentReferenceTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentReferenceTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DocumentReferenceType  $documentReferenceType
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentReferenceType $documentReferenceType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DocumentReferenceType  $documentReferenceType
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentReferenceType $documentReferenceType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentReferenceTypeRequest  $request
     * @param  \App\Models\DocumentReferenceType  $documentReferenceType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentReferenceTypeRequest $request, DocumentReferenceType $documentReferenceType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DocumentReferenceType  $documentReferenceType
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentReferenceType $documentReferenceType)
    {
        //
    }
}
