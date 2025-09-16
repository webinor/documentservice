<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Misc\Document;
use App\Models\Misc\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Requests\UpdateAttachmentRequest;
use App\Jobs\GeneratePdfThumbnail;
use App\Models\Misc\File;
use Illuminate\Http\Request;

class AttachmentController extends Controller
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
     * @param  \App\Http\Requests\StoreAttachmentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttachmentRequest $request)
    {
        // Validation
        $validated = $request->validated();

          $user = $request->get('user');
       
        try {
            
            DB::beginTransaction();

            // Vérifier que le document existe
        $document = Document::findOrFail($validated["documentId"]);

        // Sauvegarder le fichier
        //$file = $request->file('attachment');
        //$path = $file->store('attachments'); // storage/app/attachments

        // Créer l'enregistrement en base
        $attachment = Attachment::create([
            'document_id' => $document->id,
            'attachment_type_id' => $validated["attachmentType"],
           // 'file_path' => $path,
           // 'file_name' => $file->getClientOriginalName(),
            'created_by' => $user["id"],
        ]);



        $fileName = Str::random(20) . '_' . time() . '.'. $request->attachment->extension();  
        $type = $request->attachment->getClientMimeType();
        $size = $request->attachment->getSize();

        $request->attachment->move(storage_path('app/public/documents_attachments'), $fileName);
        //$path = $request->file('attachment')->store('documents'); // dans storage/app/documents


        $file = new File();

        $file->path = $fileName  ; 
        $file->type =  $type ; 
        $file->size = $size  ; 

        $attachment->file()->save($file);


        // Lancer le Job en arrière-plan
        GeneratePdfThumbnail::dispatch($attachment);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

        

        return response()->json([
            'success' => true,
            'message' => 'Fichier enregistré avec succès',
            'data' => $attachment
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request , Document $document)
    {
      //  return $document;
        
        //$attachment->load('attachment.file');
        $attachment = $document->load(['attachments' => fn($query) => $query->where('is_main', true)->with('file')]);
        
        //return $attachment;


        $path = 'documents_attachments/' . $attachment->file->path;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $disk = Storage::disk('public');
        $file = $disk->get($path);
        $mimeType = $disk->mimeType($path);
       // $mimeType = Storage::disk('public')->getMimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

     public function getMainAttachment(Request $request , Document $document)
    {
      //  return $document;
        
        //$attachment->load('attachment.file');
        $document = $document->load(['main_attachment.file']);
        
        //return $attachment;


        $path = 'documents_attachments/' . $document->main_attachment->file->path;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $disk = Storage::disk('public');
        $file = $disk->get($path);
        $mimeType = $disk->mimeType($path);
       // $mimeType = Storage::disk('public')->getMimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

        public function old_show(Attachment $attachment)
    {
         $attachment->load('file');
        
        $path = 'documents_attachments/' . $attachment->file->path;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Fichier introuvable'], 404);
        }

        $disk = Storage::disk('public');
        $file = $disk->get($path);
        $mimeType = $disk->mimeType($path);
       // $mimeType = Storage::disk('public')->getMimeType($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Misc\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function edit(Attachment $attachment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAttachmentRequest  $request
     * @param  \App\Models\Misc\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttachmentRequest $request, Attachment $attachment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Misc\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attachment $attachment)
    {
        //
    }
}
