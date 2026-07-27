<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentReferenceRequest;
use App\Http\Requests\UpdateDocumentReferenceRequest;
use App\Models\DocumentReference;
use App\Models\DocumentReferenceType;
use App\Models\Misc\File;
use App\Services\Document\DocumentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentReferenceController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentReferenceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(
    StoreDocumentReferenceRequest $request,
    DocumentService $documentService
) {

    $validated = $request->validated();

    $user = $request->get("user");

    try {

        DB::beginTransaction();


        // Vérifier que le document existe
        $document = $documentService->getDoc(
            $validated["documentId"]
        );


        /**
         * Vérifier si cette référence existe déjà
         */
        $existingReference = DocumentReference::query()
            ->where('document_id', $document->id)
            ->where(
                'document_reference_type_id',
                $validated["document_reference_type_id"]
            )
            ->where(
                'reference',
                $validated["reference"]
            )
            ->first();



        if ($existingReference) {

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Cette référence existe déjà.",
                "data" => $existingReference
            ], 200);

        }

      $reference_type = DocumentReferenceType::findOrFail($validated["document_reference_type_id"]);

        /**
         * Création de la référence
         */
        $reference = DocumentReference::create([

            "document_id" => $document->id,

            "document_reference_type_id" => $validated["document_reference_type_id"],

            "reference_type_code" => $reference_type->code,

            "reference" =>   $validated["reference"],

            "created_by" =>
                $user["id"],

            "metadata" =>
                $validated["metadata"] ?? null,

        ]);




       /**
 * Pièce jointe optionnelle
 */
if ($request->hasFile('attachment')) {


    $fileName =
        Str::random(20)
        . "_"
        . time()
        . "."
        . $request->attachment->extension();



    $type =
        $request->attachment->getClientMimeType();


    $size =
        $request->attachment->getSize();



    $request->attachment->move(

        storage_path(
            "app/public/documents_references"
        ),

        $fileName
    );



    // Création du fichier GED
    $file = new File();

    $file->path = $fileName;
    $file->type = $type;
    $file->size = $size;



    // Liaison polymorphique avec DocumentReference
    $reference->file()->save($file);



    // Metadata métier uniquement
    $reference->metadata = [
        "file_type" => $type,
        "file_size" => $size
    ];


    $reference->save();

}


        DB::commit();



        return response()->json([

            "success" => true,

            "message" => "{$reference_type->label} enregistré(e) avec succès",

            "data" => $reference

        ],201);



    } catch(\Throwable $th){

        DB::rollback();

        throw $th;
    }
}

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DocumentReference  $documentReference
     * @return \Illuminate\Http\Response
     */
    public function show(DocumentReference $documentReference)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DocumentReference  $documentReference
     * @return \Illuminate\Http\Response
     */
    public function edit(DocumentReference $documentReference)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentReferenceRequest  $request
     * @param  \App\Models\DocumentReference  $documentReference
     * @return \Illuminate\Http\Response
     */
   public function update(
    UpdateDocumentReferenceRequest $request,
    DocumentReference $documentReference
) {
    // return
    $validated = $request->validated();

    DB::transaction(function () use ($request, $validated, $documentReference) {

      $reference_type = DocumentReferenceType::findOrFail($validated["document_reference_type_id"]);


        $documentReference->update([
            'document_reference_type_id' => $validated['document_reference_type_id'],
            'reference_type_code' => $reference_type -> code,
            'reference' => $validated['reference'],
            'metadata' => $validated['metadata'] ?? $documentReference->metadata,
        ]);

        if ($request->hasFile('attachment')) {

            $fileName = Str::random(20)
                . '_' . time()
                . '.' . $request->attachment->extension();

            $type = $request->attachment->getClientMimeType();
            $size = $request->attachment->getSize();

            $request->attachment->move(
                storage_path('app/public/documents_references'),
                $fileName
            );

            $file = $documentReference->file;

            if (!$file) {
                $file = new File();
            }

            $file->path = $fileName;
            $file->type = $type;
            $file->size = $size;

            $documentReference->file()->save($file);
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Référence mise à jour avec succès.',
        'data' => $documentReference->fresh('file'),
    ]);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DocumentReference  $documentReference
     * @return \Illuminate\Http\Response
     */
    public function destroy(DocumentReference $documentReference)
    {
        //
    }
}
