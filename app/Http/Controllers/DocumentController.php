<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Finance\InvoiceProvider;
use App\Models\Misc\Attachment;
use App\Models\Misc\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class DocumentController extends Controller
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
     * @param  \App\Http\Requests\StoreDocumentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDocumentRequest $request)
    {
        try {

            DB::beginTransaction();
        
        // Récupérer les données validées par le FormRequest
    $validated = $request->validated();
    $user_connected= $request->get('user'); // récupéré du user-service

   // return ($user_connected);

   //on recupere le workflow

   // 🔹 Appel au microservice workflow
   $workflowServiceUrl = config('services.workflow_service.base_url'); // ex: http://workflow-service/api
    $workflowResponse = Http::withToken($request->bearerToken())->acceptJson()->get("$workflowServiceUrl/by-document-type/{$validated['document_type_id']}");

  //dd($workflowResponse);

   $workflowId = null;
   if ($workflowResponse->ok()) {
       $workflowId = $workflowResponse->json('id'); // récupère l'id du workflow
   }
   else{

     $workflowResponse->json();

}

    // Créer le document
    $document = Document::create([
       // 'title' => $validated['title'],
        'document_type_id' => $validated['document_type_id'],
        'department_id' => $validated['departement'] ?? null, // ✅ optionnel,
        'workflow_id' => $workflowId,
        'created_by' => $user_connected['id'], // si tu veux stocker l’utilisateur connecté
        'created_at'=>now(),
        'updated_at'=>now(),
        // autres champs génériques...
    ]);


    $facture_fournisseur = new InvoiceProvider();
    $facture_fournisseur->document_id = $document->id;
    $facture_fournisseur->amount=$validated['montant'];
    $facture_fournisseur->reference=$validated['reference'];
    $facture_fournisseur->deposit_date=now();// Carbon::parse($validated['dateDepot'])->format('Y-m-d H:i:s');
    $facture_fournisseur->save();

    // Si tu veux gérer des fichiers uploadés
    if ($request->hasFile('facture')) {

        $fileName = Str::random(20) . '_' . time() . '.'. $request->facture->extension();  
        $type = $request->facture->getClientMimeType();
        $size = $request->facture->getSize();

        $request->facture->move(storage_path('app/public/documents_attachments'), $fileName);
        //$path = $request->file('facture')->store('documents'); // dans storage/app/documents
        $document->save();

        $attachment = new Attachment();
        $attachment->document_id = $document->id;
        $attachment->source="UPLOAD";
        $attachment->save();



        $file = new File();

        
                 
        $file->path = $fileName  ; 
        $file->type =  $type ; 
        $file->size = $size  ; 

        $attachment->file()->save($file);
    }


     // 3️⃣ Création de l’instance de workflow
   //  $workflowInstanceUrl = config('services.workflow.base_url') . "/api/workflow-instances";

    $workflow =  $workflowResponse->json();

  if ($workflow) {


    $firstStep =  $workflow['steps'][0];
  

      $payload = [
         "workflow_id" => $workflow["id"],
         "department_id" => $validated["departement"] ?? null,
         "document_id" => $document->id,
         "status" => "IN_PROGRESS",
         "current_step_id" => $firstStep['id'] ?? null,
         "created_by" => $user_connected,
         'steps' => $workflow['steps'], // tableau des étapes
     ];


       $instanceResponse = Http::withToken($request->bearerToken())
            ->acceptJson()
            ->post($workflowServiceUrl."/workflow-instances", $payload);

        if ($instanceResponse->failed()) {
            
            DB::rollBack();
            $document->delete(); // supprime le doc créé
            return response()->json([
                'message' => 'Échec de l’initialisation du workflow. Document supprimé.'
            ], 500);
            
        }

        DB::commit();


        $workflowInstance = $instanceResponse->json();


           

         return response()->json([
            "message" => "Document créé avec succès et workflow démarré",
            "document" => $document,
            "workflow_instance" => $workflowInstance
        ], 201);

    }

    else{

    DB::commit();


    return response()->json([
        "message" => "Document créé avec succès et sans workflow",
        "document" => $document,
    ], 201);

    }

    } catch (\Throwable $th) {
        DB::rollback();
        throw $th;
    }

    }


    public function getDocumentsByIds(Request $request)
{
    $ids = $request->input('ids', []);
    $documents = Document::with('document_type')->whereIn('id', $ids)->get();

    return response()->json($documents);
}


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Document $document)
    {

         $document->load('document_type');

         $documents_relation = ["facture-fournisseur" => "invoice_provider"];
         
        return $document->load($documents_relation[$document->document_type->slug],'attachment.file');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function edit(Document $document)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDocumentRequest  $request
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function destroy(Document $document)
    {
        //
    }
}
