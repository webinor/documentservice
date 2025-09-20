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
use App\Models\Finance\InvoiceProvider;
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

        $existing_attachment = Attachment::whereAttachmentNumber($validated["attachment_number"])->first();

       if ($existing_attachment && $existing_attachment->document_id != $document->id) {
    return response()->json([
        'message' => 'Ce numero de piece est déjà rattaché à un autre document.',
        'errors' => [
            'attachment_number' => ['Ce numero de piece est déjà rattaché à un autre document.']
        ]
    ], 422);
}elseif($existing_attachment && $existing_attachment->document_id == $document->id){


  return  $response = response()->json([
     'success' => true,
    'existing_attachment' => $existing_attachment
], 201);
   
    



}
elseif(!$existing_attachment){

    //on verifie si le document en cours a deja un attachment de ce type, meme si le numero de piece est different

    $attachment_document = $document->attachments()->whereAttachmentTypeId($validated["attachmentType"])->first();

    if ($attachment_document) {//alors on met  jour l'exixtant
        
        $attachment_document->attachment_number = $validated["attachment_number"];
        $attachment_document->save();
       // return $attachment_document;

       DB::commit();

         return  $response = response()->json([
     'success' => true,
    'existing_attachment' => $attachment_document
], 201);
        
    }

}

           if ($validated["source"] == "new") {

        // Créer l'enregistrement en base
        $attachment = Attachment::create([
            'document_id' => $document->id,
            'attachment_type_id' => $validated["attachmentType"],
            'attachment_number' => $validated["attachment_number"] ?? null,
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


              $response = response()->json([
            'success' => true,
            'message' => 'Fichier enregistré avec succès',
            'data' => $attachment
        ], 201);

        }
        elseif ($validated["source"] == "exist") {  ////////////on duplique le fichier
            
            $document = Document::
          with("main_attachment.file")
          ->whereReference($validated["reference"])->first();



if (!$document || !$document->main_attachment || !$document->main_attachment->file) {

       return response()->json([
        'message' => 'Reference introuvable.',
        'errors' => [
            'reference' => ['Reference introuvable.']
        ]
    ], 422);
    throw new \Exception("Fichier introuvable");
}

$originalFile = $document->main_attachment->file;

// 1️⃣ Chemin source et nouveau nom
$folder = 'documents_attachments';
$originalPath = $folder . '/' . $originalFile->path;
$newFileName = Str::random(20) . '_' . time() . '.' . pathinfo($originalFile->path, PATHINFO_EXTENSION);
$newPath = $folder . '/' . $newFileName;

// 2️⃣ Copier le fichier dans le même dossier
Storage::disk('public')->copy($originalPath, $newPath);

// 3️⃣ Créer la nouvelle instance File
$newFile = new File();
$newFile->path = $newFileName;
$newFile->type = $originalFile->type;
$newFile->size = $originalFile->size;
//$newFile->save();

// 4️⃣ Créer le nouvel attachment et lier au document
$newAttachment = new Attachment();
$newAttachment->is_main = false; // ou true selon le cas
$newAttachment->attachment_type_id = $validated["attachmentType"]; 
$newAttachment->attachment_number = $validated["attachment_number"]; 
$newAttachment->created_by = $user["id"];


$document->attachments()->save($newAttachment);

$newAttachment->file()->save($newFile);


  

$response = response()->json([
     'success' => true,
    'new_file' => $newFile,
    'new_attachment' => $newAttachment
], 201);

          
        }

          

        DB::commit();


        return $response;


        


        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }

        

      
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\Attachment  $attachment
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request , Attachment $attachment)
    {
        //return $document;
        
        //$attachment->load('attachment.file');
        //$attachment = $document->load(['attachments' => fn($query) => $query->where('is_main', true)->with('file')]);
        $attachment = $attachment->load(['file']);
        
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
