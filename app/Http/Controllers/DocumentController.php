<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Jobs\GeneratePdfThumbnail;
use App\Models\Finance\InvoiceProvider;
use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
use App\Models\Misc\DocumentType;
use App\Models\Misc\File;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    public function searchDocumentByReference (Request $request) {

    $reference = $request->query('reference_engagement');

    $document = Document::whereReference($reference)->first();

    if (!$document) {

        return response()->json(['message' => 'Document non trouvé'], 404);

    }

    return response()->json(['success'=>true, 'document' => $document , 'message' => 'Document trouvé']);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function enrichAttachments($user , $attachments)
    {
        // Liste des attachment_type_id
$attachmentTypeIds = $attachments->pluck("attachment_type_id")->unique()->values();


// Appel au microservice user
return $response = Http::withToken(request()->bearerToken())
    ->acceptJson()
    ->post(config("services.user_service.base_url") . "/permissions/by-attachment-types", [
        "user_id" => $user['id'],
        "attachment_type_ids" => $attachmentTypeIds,
    ]);

    

// Vérifier la réponse
if ($response->successful()) {
    $permissions = collect($response->json()); 
    // Format attendu: [ attachment_type_id => ['view' => true, 'edit' => false, ...] ]

    // Fusionner les permissions dans chaque attachment
    $attachments = $attachments->map(function ($attachment) use ($permissions) {
        $attachment["permissions"] = $permissions[$attachment["attachment_type_id"]] ?? [];
        return $attachment;
    });
}
    }

    public function getAttachments($documentId)
    {
        $document = Document::with([
            "attachments.attachmentType",
            "attachments.file",
        ])->findOrFail($documentId);


        $user =   request()->get('user');

        $attachments = $document->attachments->map(function ($attachment) {
            return [
                "id" => $attachment->id,
                "attachment_number" => $attachment->attachment_number,
                "attachment_type_id" => $attachment->attachment_type_id,
                "attachment_type_slug" => $attachment->attachmentType
                    ? $attachment->attachmentType->slug
                    : "--",
                "name" => $attachment->attachmentType
                    ? $attachment->attachmentType->name
                    : "--",
                "created_by_id" => $attachment->created_by,
                "created_at" => $attachment->created_at->format("d/m/Y à H:i"),
                "url" => $attachment->file->path ?? "#",
            ];
        });

     //  return $attachments = $this->enrichAttachments($user , $attachments);;

        // Extraire tous les IDs uniques d'utilisateurs
        $userIds = $attachments
            ->pluck("created_by_id")
            ->unique()
            ->values()
            ->all();

        $users = [];
        if (!empty($userIds)) {
            // Appel au microservice User
            $response = Http::withToken(request()->bearerToken())
                ->acceptJson()
                ->get(config("services.user_service.base_url") . "/getByIds", [
                    "ids" => implode(",", $userIds),
                ]);

            if ($response->successful()) {
                // Exemple de retour attendu: [{id:1, name:"Leanne"}, {id:2, name:"Gabin"}, ...]
                $users = collect($response->json())->keyBy("id");
            }
        }

        // Enrichir les attachments avec le nom
        $attachments = $attachments->map(function ($att) use ($users) {
            $userName =
                $users[$att["created_by_id"]]["name"] ??
                "Utilisateur ID: {$att["created_by_id"]}";
            $attachment_number = $att["attachment_number"]
                ? " #" . $att["attachment_number"] . " "
                : " ";
            $by = "par";
            return [
                "id" => $att["id"],
                "name" => "{$att["name"]}$attachment_number{$by} {$userName} le {$att["created_at"]}",
                "url" => $att["url"],
                "slug"=>$att["attachment_type_slug"]
            ];
        });

        return response()->json([
            "success" => true,
            "data" => ["attachments" => $attachments],
        ]);
    }

    public function getAvailableActions(Request $request, $id)
    {
        $user = $request->get("user");

        $document = Document::findOrFail($id);
        $documentType = $document->document_type_id;

        // Vérifie bien que $user contient bien une clé "id"
        if (!isset($user["id"])) {
            return response()->json(
                [
                    "error" => "User ID manquant",
                ],
                400
            );
        }

        try {
            $response = Http::withHeaders([
                "Accept" => "application/json",
            ])
                ->timeout(10)
                ->get(
                    config("services.user_service.base_url") .
                        "/permissions/actions?user_id={$user["id"]}&document_type={$documentType}"
                    /*[
            
                'user_ido' => $user['id'],
                'document_type' => $documentType,
          //  ],
            //'timeout' => 10, // éviter blocage long
        ]*/
                );

            if ($response->failed()) {
                return response()->json(
                    [
                        "error" => "Erreur lors de l’appel au service user",
                        "details" => $response->body(),
                    ],
                    $response->status()
                );
            }

            return response()->json(
                ["success" => true, "data" => ["actions" => $response->json()]],
                200
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    "error" =>
                        "Exception levée lors de l’appel au service user",
                    "details" => $e->getMessage(),
                ],
                500
            );
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
        $characters = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        $charactersLength = strlen($characters);

        do {
            $reference = "";
            for ($i = 0; $i < $length; $i++) {
                $reference .= $characters[random_int(0, $charactersLength - 1)];
            }

            // Vérifier en base si la référence existe déjà
            $exists = Document::where("reference", $reference)->exists();
        } while ($exists); // Regénérer tant que la référence existe

        return $reference;
    }

    public function attachmentMapping(Document $linkedDocument)  {

        $attachmentTypes = AttachmentType::pluck('id', 'name')->toArray();

//throw new Exception(json_encode($attachmentTypes), 1);

// On charge tous les attachment_types une fois
$attachmentTypes = AttachmentType::pluck('id', 'name')->toArray();

$documentAttachmentMap = [
    "invoice_provider" => [
        "attachment_type_name" => "Facture",
        "attachment_type_id"   => $attachmentTypes["Facture"] ?? null,
    ],
    "order" => [
        "attachment_type_name" => "Bon de commande",
        "attachment_type_id"   => $attachmentTypes["Bon de commande"] ?? null,
    ],
    "delivery_note" => [
        "attachment_type_name" => "Bon de livraison",
        "attachment_type_id"   => $attachmentTypes["Bon de livraison"] ?? null,
    ],
    "payment" => [
        "attachment_type_name" => "Ordre de virement",
        "attachment_type_id"   => $attachmentTypes["Ordre de virement"] ?? null,
    ],
    "treasury" => [
        "attachment_type_name" => "Attestation de règlement",
        "attachment_type_id"   => $attachmentTypes["Attestation de règlement"] ?? null,
    ],
];


                //   $relationSlug = $linkedDocument->getTable(); // ou un champ slug dans ta BDD

                   $specializedIds = [
    'invoice_provider' => InvoiceProvider::pluck('document_id')->toArray(),
   // 'order' => \App\Models\Order::pluck('document_id')->toArray(),
];

$relationSlug = null;
foreach ($specializedIds as $slug => $ids) {
    if (in_array($linkedDocument->id, $ids)) {
        $relationSlug = $slug;
        break;
    }
}

throw new Exception(json_encode($attachmentTypes), 1);


if (!empty($documentAttachmentMap[$relationSlug]['attachment_type_id'])) {
    return $documentAttachmentMap[$relationSlug]['attachment_type_id'];
}

//throw new Exception(json_encode($relationSlug), 1);

        
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
            $user_connected = $request->get("user"); // récupéré du user-service

            // return ($user_connected);

            //on recupere le workflow

            // 🔹 Appel au microservice workflow
            $workflowServiceUrl = config("services.workflow_service.base_url"); // ex: http://workflow-service/api
            $workflowResponse = Http::withToken($request->bearerToken())
                ->acceptJson()
                ->get(
                    "$workflowServiceUrl/by-document-type/{$validated["document_type_id"]}"
                );

            //dd($workflowResponse);

            $workflowId = null;
            if ($workflowResponse->ok()) {
                $workflowId = $workflowResponse->json("id"); // récupère l'id du workflow
            } else {
                $workflowResponse->json();
            }

            $reference = $this->generateUniqueReference(6); // ex: longueur 6
            // Créer le document
            $document = Document::create([
                "title" => $validated["titre"],
                "document_type_id" => $validated["document_type_id"],
                "department_id" => $validated["departement"] ?? null, // ✅ optionnel,
                "workflow_id" => $workflowId,
                "created_by" => $user_connected["id"], // si tu veux stocker l’utilisateur connecté
                "created_at" => now(),
                "updated_at" => now(),
                "reference" => $reference,
                // autres champs génériques...
            ]);
 
            $facture = new InvoiceProvider();
            $facture->document_id = $document->id;
            $facture->amount = $validated["montant"];
            $facture->provider = $validated["prestataire"];
            $facture->provider_reference =$validated["reference_fournisseur"];
            $facture->deposit_date = now(); // Carbon::parse($validated['dateDepot'])->format('Y-m-d H:i:s');
            $facture->save();


            $documentType = DocumentType::find($validated["document_type_id"]);

            $className = $documentType->class_name; // ex: "\App\Models\ItSupplier"
            $relationName = $documentType->relation_name; // ex: "medical_supplier"

            if (class_exists($className)) {
                $instance = new $className();
                 $facture->$relationName()->save($instance);
            } else {
                throw new \Exception("Classe {$className} introuvable !");
            }

            //return $facture;

            // Si tu veux gérer des fichiers uploadés
            if ($request->hasFile("facture")) {
                $document->save();

                $fileName =
                    Str::random(20) .
                    "_" .
                    time() .
                    "." .
                    $request->facture->extension();
                $type = $request->facture->getClientMimeType();
                $size = $request->facture->getSize();

                $request->facture->move(
                    storage_path("app/public/documents_attachments"),
                    $fileName
                );
                //$path = $request->file('facture')->store('documents'); // dans storage/app/documents

                $attachment = new Attachment();
                $attachment->document_id = $document->id;
                $attachment->is_main = true;
                $attachment->source = "UPLOAD";
                $attachment->created_by = $user_connected["id"]; // stocker l’utilisateur connecté
                $attachment->attachment_type_id = AttachmentType::whereSlug(
                    "facture-originale"
                )->first()->id;
                $attachment->save();

                $file = new File();

                $file->path = $fileName;
                $file->type = $type;
                $file->size = $size;

                $attachment->file()->save($file);

                // Lancer le Job en arrière-plan
                GeneratePdfThumbnail::dispatch($attachment);
            }


            //Si la reference de l'engagement correspond a un document dans le systeme, on associe directement a la facture
             if (isset($validated["linkedDocument"])) {

                    


                ////////////on duplique le fichier

                $linkedDocument = Document::with(["main_attachment.file", "document_type"])
                    ->whereReference($validated["linkedDocument"])
                    ->first();

                if (
                    !$linkedDocument ||
                    !$linkedDocument->main_attachment ||
                    !$linkedDocument->main_attachment->file
                ) {
                    return response()->json(
                        [
                            "message" => "Reference introuvable.",
                            "errors" => [
                                "reference" => ["Reference introuvable."],
                            ],
                        ],
                        422
                    );
                    throw new \Exception("Fichier introuvable");
                }

                $originalFile = $linkedDocument->main_attachment->file;

                // 1️⃣ Chemin source et nouveau nom
                $folder = "documents_attachments";
                $originalPath = $folder . "/" . $originalFile->path;
                $newFileName =
                    Str::random(20) .
                    "_" .
                    time() .
                    "." .
                    pathinfo($originalFile->path, PATHINFO_EXTENSION);
                $newPath = $folder . "/" . $newFileName;

                // 2️⃣ Copier le fichier dans le même dossier
                Storage::disk("public")->copy($originalPath, $newPath);

                // 3️⃣ Créer la nouvelle instance File
                $newFile = new File();
                $newFile->path = $newFileName;
                $newFile->type = $originalFile->type;
                $newFile->size = $originalFile->size;
                //$newFile->save();

                // 4️⃣ Créer le nouvel attachment et lier au document
                $newAttachment = new Attachment();
                $newAttachment->is_main = false; // ou true selon le cas
                $newAttachment->attachment_type_id =$this->attachmentMapping($linkedDocument);
                //$newAttachment->attachment_number =$validated["attachment_number"];
                $newAttachment->created_by = $user_connected["id"];

                $document->attachments()->save($newAttachment);

                $newAttachment->file()->save($newFile);

                /**return  response()->json(
                    [
                        //"success" => true,
                        "new_file" => $newFile,
                        "new_attachment" => $newAttachment,
                    ],
                    201
                );/**/



            }





            // 3️⃣ Création de l’instance de workflow
            //  $workflowInstanceUrl = config('services.workflow.base_url') . "/api/workflow-instances";

            $workflow = $workflowResponse->json();

            if ($workflow) {
                $firstStep = $workflow["steps"][0];

                $payload = [
                    "workflow_id" => $workflow["id"],
                    "department_id" => $validated["departement"] ?? null,
                    "document_id" => $document->id,
                    "status" => "IN_PROGRESS",
                    "current_step_id" => $firstStep["id"] ?? null,
                    "created_by" => $user_connected,
                    "steps" => $workflow["steps"], // tableau des étapes
                ];

                DB::commit();

                //  return
                //["ok"];
                $instanceResponse = Http::withToken($request->bearerToken())
                    ->acceptJson()
                    ->post(
                        $workflowServiceUrl . "/workflow-instances",
                        $payload
                    );

                if ($instanceResponse->failed()) {
                    DB::rollBack();
                    $document->delete(); // supprime le doc créé
                    return response()->json(
                        [
                            "message" =>
                                "Échec de l’initialisation du workflow. Document supprimé.",
                            "backend-message" => $instanceResponse->json(),
                        ],
                        500
                    );
                }

                //DB::commit();

                $workflowInstance = $instanceResponse->json();

                return response()->json(
                    [
                        "success" => true,
                        "message" =>
                            "Document créé avec succès et workflow démarré",
                        "document" => $document,
                        "workflow_instance" => $workflowInstance,
                    ],
                    201
                );
            } else {
                DB::commit();

                return response()->json(
                    [
                        "message" =>
                            "Document créé avec succès et sans workflow",
                        "document" => $document,
                    ],
                    201
                );
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function getDocumentsByIds(Request $request)
    {
        $ids = $request->input("ids", []);
        $documentTypes = $request->input("documentTypes", []);
        $filters = $request->input("filters", []); // tableau associatif de filtres dynamiques

        /*$documents = Document::with(["document_type", "invoice_provider"])
            ->whereIn("id", $ids)
            ->get()*/

              $query = Document::query();

                // Filtre par IDs
    if (!empty($ids)) {
        $query->whereIn('id', $ids);
    }

        // Filtre par relations / types de document
    if (!empty($documentTypes)) {
        $query->where(function ($q) use ($documentTypes) {
            foreach ($documentTypes as $relation) {
                $q->whereHas($relation);
            }
        });
    }

     // Filtre par statut
    if (!empty($filters['status'])) {
        $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', $filters['status']);
        $query->whereIn('status', $statuses);
    }

  // Filtre par montant dans InvoiceProvider
if (!empty($filters['amount'])) {
    $query->whereHas('invoice_provider', function ($q) use ($filters) {
        switch ($filters['amount']) {
            case 'lt_100k':
                $q->where('amount', '<', 100000);
                break;
            case '100k_500k':
                $q->whereBetween('amount', [100000, 500000]);
                break;
            case 'gt_500k':
                $q->where('amount', '>', 500000);
                break;
        }
    });
}


    // Filtre par fournisseur (via InvoiceProvider)
    if (!empty($filters['document_type_id'])) {
        $document_type_id = $filters['document_type_id'];
        $query->whereHas('document_type', function ($q) use ($document_type_id) {
            $q->whereId($document_type_id); // ou le champ correct dans DocumentType
        });
    }

        // Filtre par fournisseur (via InvoiceProvider)
    if (!empty($filters['fournisseur_id'])) {
        $fournisseurId = $filters['fournisseur_id'];
        $query->whereHas('invoice_provider', function ($q) use ($fournisseurId) {
            $q->where('id', $fournisseurId); // ou le champ correct dans InvoiceProvider
        });
    }

   // Filtre par date
if (!empty($filters['date_start']) && !empty($filters['date_end'])) {
    $query->whereBetween('created_at', [$filters['date_start'], $filters['date_end']]);
} elseif (!empty($filters['date_start'])) {
    $query->whereDate('created_at', '>=', $filters['date_start']);
} elseif (!empty($filters['date_end'])) {
    $query->whereDate('created_at', '<=', $filters['date_end']);
}

   
    // Charger les relations
    $query->with(array_merge(['document_type'], $documentTypes));

    $documents = $query->get()->map(function ($doc) {
        return [
            "id" => $doc->id,
            "title" => $doc->title,
            "document_type_name" => $doc->document_type->name,
            "document_type_id" => $doc->document_type_id,
            "type" => $doc->document_type->name,
            "status" => $doc->status,
            "amount" => $doc->invoice_provider->amount,
            "created_at" => $doc->created_at,
            "created_by" => $doc->created_by,
            "acteur_principal" => $doc->invoice_provider->provider ?? null, // ou autre champ clé
        ];
    });

    return response()->json($documents);

/*
            $documents = Document::whereIn("id", $ids)
    ->where(function ($query) use ($documentTypes) {
        foreach ($documentTypes as $relation) {
            $query->WhereHas($relation);
        }
    })
    ->with(array_merge(["document_type"], $documentTypes))
    ->get()
        

             ->map(function ($doc) {
                return [
                    "id" => $doc->id,
                    "title" => $doc->title,
                    "document_type_name" => $doc->document_type->name,
                    "document_type_id" => $doc->document_type_id,
                    "type" => $doc->document_type->name, // si tu veux le libellé du type
                    "status" => $doc->status,
                    "created_at" => $doc->created_at, //->format('d-m-Y'),
                    "created_by" => $doc->created_by,
                    "acteur_principal" => $doc->acteur_principal, // ici ton fournisseur lié
                ];
            }); 

        return response()->json($documents);*/
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Document $document)
    {
        $document->load("document_type");

        $documents_relation = [
            "facture-fournisseur-medical" => "invoice_provider.ledger_code",
        ];

        return $document->load(
            $documents_relation[$document->document_type->slug],
            "attachments.file",
            "secondary_attachments"
        );
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
