<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Jobs\GeneratePdfThumbnail;
use App\Models\Finance\InvoiceProvider;
use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
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


    public function getAttachments($documentId)
    {
        $document = Document::with(['attachments.attachmentType','attachments.file'])->findOrFail($documentId);

        $attachments = $document->attachments->map(function($attachment) {
            return [
                'id' => $attachment->id,
                'name' => $attachment->attachmentType->name,
                'created_by_id' => $attachment->created_by,
                'created_at' => $attachment->created_at->format('d/m/Y à H:i'),
                'url' => $attachment->file->path ?? '#',
            ];
        });

        // Extraire tous les IDs uniques d'utilisateurs
        $userIds = $attachments->pluck('created_by_id')->unique()->values()->all();

        $users = [];
        if (!empty($userIds)) {
            // Appel au microservice User
            $response = Http::withToken(request()->bearerToken())
                ->acceptJson()
                ->get(config('services.user_service.base_url') . '/getByIds', [
                    'ids' => implode(',', $userIds)
                ]);

            if ($response->successful()) {
                // Exemple de retour attendu: [{id:1, name:"Leanne"}, {id:2, name:"Gabin"}, ...]
                  $users = collect($response->json())->keyBy('id');
            }
        }

        // Enrichir les attachments avec le nom
        $attachments = $attachments->map(function($att) use ($users) {
            $userName = $users[$att['created_by_id']]['name'] ?? "Utilisateur ID: {$att['created_by_id']}";
            return [
                'id' => $att['id'],
                'name' => "{$att['name']} par {$userName} le {$att['created_at']}",
                'url' => $att['url'],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => ['attachments' => $attachments]
        ]);
    }

public function getAvailableActions(Request $request, $id)
{
    $user = $request->get("user"); 

    $document = Document::findOrFail($id);
    $documentType = $document->document_type_id;
    
    // Vérifie bien que $user contient bien une clé "id"
    if (!isset($user['id'])) {
        return response()->json([
            'error' => 'User ID manquant'
        ], 400);
    }
    
    try {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->timeout(10)->get(config('services.user_service.base_url') . "/permissions/actions?user_id={$user['id']}&document_type={$documentType}", 
        /*[
            
                'user_ido' => $user['id'],
                'document_type' => $documentType,
          //  ],
            //'timeout' => 10, // éviter blocage long
        ]*/);
    
        if ($response->failed()) {
            return response()->json([
                'error' => 'Erreur lors de l’appel au service user',
                'details' => $response->body()
            ], $response->status());
        }
    
        return response()->json(["success"=>true,"data"=>["actions"=>$response->json()]], 200);
    
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception levée lors de l’appel au service user',
            'details' => $e->getMessage(),
        ], 500);
    }
    
}


/**
 * Génère une référence interne unique en base alphanumérique sans caractères ambigus
 * @param int $length
 * @return string
 * @throws \Exception
 */
function generateUniqueReference(int $length = 8): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $charactersLength = strlen($characters);

    do {
        $reference = '';
        for ($i = 0; $i < $length; $i++) {
            $reference .= $characters[random_int(0, $charactersLength - 1)];
        }

        // Vérifier en base si la référence existe déjà
        $exists = Document::where('reference', $reference)->exists();
    } while ($exists); // Regénérer tant que la référence existe

    return $reference;
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

            $reference = $this->generateUniqueReference(6); // ex: longueur 6
                // Créer le document
                $document = Document::create([
                    'title' => $validated['titre'],
                    'document_type_id' => $validated['document_type_id'],
                    'department_id' => $validated['departement'] ?? null, // ✅ optionnel,
                    'workflow_id' => $workflowId,
                    'created_by' => $user_connected['id'], // si tu veux stocker l’utilisateur connecté
                    'created_at'=>now(),
                    'updated_at'=>now(),
                    'reference'=>$reference
                    // autres champs génériques...
                ]);


                $facture_fournisseur = new InvoiceProvider();
                $facture_fournisseur->document_id = $document->id;
                $facture_fournisseur->amount=$validated['montant'];
                $facture_fournisseur->provider=$validated['prestataire'];
                $facture_fournisseur->provider_reference=$validated['reference_fournisseur'];
                $facture_fournisseur->deposit_date=now();// Carbon::parse($validated['dateDepot'])->format('Y-m-d H:i:s');
                $facture_fournisseur->save();

                //return $facture_fournisseur;

                // Si tu veux gérer des fichiers uploadés
                if ($request->hasFile('facture')) {
                    
                    $document->save();

                    $fileName = Str::random(20) . '_' . time() . '.'. $request->facture->extension();  
                    $type = $request->facture->getClientMimeType();
                    $size = $request->facture->getSize();

                    $request->facture->move(storage_path('app/public/documents_attachments'), $fileName);
                    //$path = $request->file('facture')->store('documents'); // dans storage/app/documents

                    $attachment = new Attachment();
                    $attachment->document_id = $document->id;
                    $attachment->is_main = true;
                    $attachment->source="UPLOAD";
                    $attachment->created_by = $user_connected['id']; // si tu veux stocker l’utilisateur connecté
                    $attachment->attachment_type_id = AttachmentType::whereSlug("facture-originale")->first()->id;
                    $attachment->save();



                    $file = new File();
            
                    $file->path = $fileName  ; 
                    $file->type =  $type ; 
                    $file->size = $size  ; 

                    $attachment->file()->save($file);


                    // Lancer le Job en arrière-plan
                    GeneratePdfThumbnail::dispatch($attachment);
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

                  DB::commit();

            //  return 
              //["ok"];
                $instanceResponse = Http::withToken($request->bearerToken())
                        ->acceptJson()
                        ->post($workflowServiceUrl."/workflow-instances", $payload);

                    if ($instanceResponse->failed()) {
                        
                        DB::rollBack();
                        $document->delete(); // supprime le doc créé
                        return response()->json([
                            'message' => 'Échec de l’initialisation du workflow. Document supprimé.',
                            'backend-message'=>$instanceResponse->json()
                        ], 500);
                        
                    }

                    //DB::commit();


                    $workflowInstance = $instanceResponse->json();


                    

                    return response()->json([
                        "success" => true,
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
    $documents = Document::with(['document_type', 'invoice_provider'])->whereIn('id', $ids)->get()
   // return response()->json($documents);

   /**/ ->map(function ($doc) {
        return [
            'id'              => $doc->id,
            'title'           => $doc->title,
            'document_type_name'=>$doc->document_type->name,
            'document_type_id'=>$doc->document_type_id,
            'type'            => $doc->document_type->name, // si tu veux le libellé du type
            'status'          => $doc->status,
            'created_at'      => $doc->created_at,//->format('d-m-Y'),
            'created_by'      => $doc->created_by,
            'acteur_principal'=> $doc->acteur_principal // ici ton fournisseur lié
        ];
    });/**/

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
         
        return $document->load($documents_relation[$document->document_type->slug],'attachments.file');
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
