<?php

namespace App\Http\Controllers;

use App\Models\Misc\Document;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Jobs\GeneratePdfThumbnail;
use App\Models\Finance\InvoiceProvider;
use App\Models\Folder;
use App\Models\Misc\Attachment;
use App\Models\Misc\AttachmentType;
use App\Models\Misc\DocumentType;
use App\Models\Misc\File;
use App\Services\DocumentChildHandler;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; // package barryvdh/laravel-dompdf


class DocumentController extends Controller
{

     private DocumentChildHandler $childHandler;
     private $documents_relation = [
            "facture-fournisseur-medical" => "invoice_provider.ledger_code",
            "facture-fournisseur-informatique" => "invoice_provider",
            "facture-note-honoraire" => "invoice_provider",
            "papier-taxi" => "taxi_paper",
            "note-de-frais" => "fee_note",
            "demande-d-absence"=>"absence_request"
        ]; 

    public function __construct(DocumentChildHandler $childHandler)
    {
        $this->childHandler = $childHandler;
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


    public function notifyBeneficiary(Request $request)
    {
        // 1. Validation
        $request->validate([
            'document' => 'required|integer|exists:documents,id',
        ]);

        $documentId = $request->input('document');
        $document = Document::with('document_type')->find($documentId);
        $child = $document->{$document->document_type->relation_name};

        // 2. Exemple : vérifier si le document permet la notification
        if (!$child->beneficiary) {
            return response()->json([
                'success' => false,
                'data' => [
                    'required_type' => 'beneficiary'
                ]
            ]);
        }


        $total = collect($child->rides)->reduce(function ($carry, $item) {
    return $carry + (int) ($item['montant'] ?? 0);
}, 0);

      //  return config("services.user_service.base_url") . ($child->beneficiary);
        //on recupere les information sur le beneficiaire
        $response = Http::withToken(request()->bearerToken())
    ->acceptJson()
    ->get(
        config("services.user_service.base_url") . "/" .($child->beneficiary)
    );

    // Vérifier la réponse
        if ($response->successful()) {



                    $response_event = Http::withToken(request()->bearerToken())
                    ->acceptJson()
                    ->post(
                        config("services.user_service.base_url") . "/events/dispatch/init-confirm-payment-receive",
                        [
                           // "event_type" => "USER_DOCUMENT_VALIDATED",
                            "payload" => [
                               // "user_id" => $userId,
                                "beneficiary" => $child->beneficiary,
                                "amount" => $total,
                            ]
                        ]
                    );

                if (!$response_event->successful()) {
                    return response()->json([
                        "success" => false,
                        "message" => "Impossible de dispatcher l’évènement au User-Service.",
                        "details" => $response->json()
                    ], $response->status());
                }

             //   return $response->json();

       // return  $response;
            
        }
        else{

            // Réponse KO → on gère l’erreur
    return response()->json([
        "success" => false,
        "message" => "Impossible de récupérer les informations de l’utilisateur.",
        "details" => $response->json()
    ], $response->status());

        }

        // 3. Envoyer OTP ou demande de signature selon ton workflow
        // $otp = rand(100000, 999999);
        // $document->otp_code = $otp;
        // $document->otp_sent_at = now();
        // $document->save();

        // Ici tu peux envoyer un SMS ou WhatsApp (mtn, orange, twilio, etc.)
        // SmsService::send($document->beneficiary_phone, "Votre code OTP est $otp");

      //  return $response_event->json();

        return response()->json([
            'success' => true,
            'message' => 'OTP envoyé avec succès au bénéficiaire.',
            'user'=>$response->json()['user'],
            'transaction_code'=>$response_event->json()['transaction_code']
        ]);
    }

     /**
     * Télécharge le document au format PDF
     */
    public function download_document(Request $request , Document $document)
    {
        // Vérifier si l'utilisateur peut accéder au document
        //$this->authorize('view', $document);

        //return $document;
       //return new Exception(json_encode($document));

       $document->load('document_type');

       $document = $this->enrichDocument($document , $request->bearerToken());


        // Chercher le template selon le type de document
        $template = $document->document_type->slug ?? null;

        if (!$template || !view()->exists("templates.$template")) {
            abort(404, "Template $template introuvable");
        }

        // Générer le PDF depuis le template Blade
        $pdf = Pdf::loadView("templates.$template", [
            'document' => $document
        ]);

        //new Exception(json_encode($template));

        // Nom du fichier pour le téléchargement
        $fileName = $template . '-' . $document->id . '.pdf';

        // Retourner le PDF en téléchargement
        return $pdf->download($fileName);
    }

    public function download($id)
    {
        $document = Document::with("main_attachment.file")->findOrFail($id);

        $file = $document->main_attachment->file;
        $path = storage_path("app/public/documents_attachments/" . $file->path);

        if (!file_exists($path)) {
            abort(404, "Fichier introuvable");
        }

        // Récupère l'extension réelle
        $extension = pathinfo($file->path, PATHINFO_EXTENSION);

        // Définir le nom de téléchargement avec l'extension correcte
        $downloadName = $document->title . ($extension ? ".{$extension}" : "");

        return response()->download($path, $downloadName);
    }

    public function getDetails(Request $request, $id)
    {
        // Récupérer l'utilisateur courant (ou depuis un paramètre)
        $user = $request->get("user");

        $userId = $user["id"];
        if (!$userId) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Utilisateur non identifié.",
                ],
                401
            );
        }

        $document = Document::with([
            "folder",
            "document_type",
            "main_attachment.file" /*'history.user', 'relatedDocuments'*/,
        ])->findOrFail($id);

        $creator = $document->creator();
        // 🧠 Déterminer le type de fichier
        $fileType = $document->main_attachment->file->type ?? null;

        $canView = $document->userCan(
            request()->bearerToken(),
            $user,
            $document,
            "view_all"
        );

        if (!$canView || $document->workflow_id) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Vous n'avez pas la permission de consulter ce document.",
                ],
                403
            );
        }
        $canUpdate = $document->userCan(
            request()->bearerToken(),
            $user,
            $document,
            "update"
        );
        $canDelete = $document->userCan(
            request()->bearerToken(),
            $user,
            $document,
            "delete"
        );
        $canShare = $document->userCan(
            request()->bearerToken(),
            $user,
            $document,
            "share"
        );

        // 🗂️ Construire les pathSegments
        $pathSegments = $document->folder
            ? $document->folder->getPathSegments()
            : [];

        // 📦 Définir les icônes selon le type MIME
        $attachmentType = $fileType;
        $attachmentIcon = "📄";
        $attachmentSlug = "autre";

        if ($attachmentType) {
            switch (true) {
                case str_contains($attachmentType, "pdf"):
                    $attachmentIcon = "📕";
                    $attachmentSlug = "pdf";
                    break;
                case str_contains($attachmentType, "image"):
                    $attachmentIcon = "🖼️";
                    $attachmentSlug = "image";
                    break;
                case str_contains($attachmentType, "word"):
                case str_contains(
                    $attachmentType,
                    "officedocument.wordprocessingml"
                ):
                    $attachmentIcon = "📘";
                    $attachmentSlug = "word";
                    break;
                case str_contains($attachmentType, "excel"):
                case str_contains($attachmentType, "spreadsheet"):
                    $attachmentIcon = "📗";
                    $attachmentSlug = "excel";
                    break;
                case str_contains($attachmentType, "powerpoint"):
                case str_contains($attachmentType, "presentation"):
                    $attachmentIcon = "📙";
                    $attachmentSlug = "powerpoint";
                    break;
                case str_contains($attachmentType, "zip") ||
                    str_contains($attachmentType, "compressed"):
                    $attachmentIcon = "🗜️";
                    $attachmentSlug = "zip";
                    break;
                case str_contains($attachmentType, "audio"):
                    $attachmentIcon = "🎵";
                    $attachmentSlug = "audio";
                    break;
                case str_contains($attachmentType, "video"):
                    $attachmentIcon = "🎬";
                    $attachmentSlug = "video";
                    break;
            }
        }

        // 🗂️ Construire la structure de retour
        $response = [
            "id" => $document->id,
            "title" => $document->title,
            "type" => $document->document_type->name ?? "Autre document",
            "folderPath" => isset($document->folder)
                ? $document->folder->full_path
                : null,
            "pathSegments" => $pathSegments, // ✅ ajouté
            "date_creation" => $document->created_at,
            "created_by" => $creator ?? "Système",
            "attachment_type" => $attachmentType,
            "preview_url" => $document->main_attachment
                ? url(
                    "storage/documents_attachments/" .
                        $document->main_attachment->file->path
                )
                : null,
            "download_url" => route("documents.download", [
                "id" => $document->id,
            ]),

            // 🔖 Métadonnées dynamiques (champs spécifiques à ce type de document)
            //'metadata' => $document->metadata ?? [],
            "metadata" => [
                "Titre" => $document->title,
                "Créé le" => $document->created_at,
                "Référence" => $document->reference,
            ],

            // 🔐 Permissions calculées
            "permissions" => [
                "lecture" => $canView,
                "modification" => $canUpdate,
                "suppression" => $canDelete,
                "partage" => $canShare,
            ],

            // 📜 Historique
            "history" => [] /* $document->history->map(function ($entry) {
                return [
                    'user' => $entry->user->name ?? 'Système',
                    'action' => $entry->action,
                    'date' => $entry->created_at->format('d/m/Y H:i'),
                ];
            })*/,

            // 🔗 Documents liés
            "related" => [] /*$document->relatedDocuments->map(function ($related) {
                return [
                    'id' => $related->id,
                    'title' => $related->title,
                ];
            }),*/,
        ];

        return response()->json(["success" => true, "document" => $response]);
    }

    public function searchDocumentByReference(Request $request)
    {
        $reference = $request->query("reference_engagement");

        $document = Document::whereReference($reference)->first();

        if (!$document) {
            return response()->json(["message" => "Document non trouvé"], 404);
        }

        return response()->json([
            "success" => true,
            "document" => $document,
            "message" => "Document trouvé",
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function enrichAttachments($user, $attachments)
    {
        // Liste des attachment_type_id
        $attachmentTypeIds = $attachments
            ->pluck("attachment_type_id")
            ->unique()
            ->values();

        // Appel au microservice user
        return $response = Http::withToken(request()->bearerToken())
            ->acceptJson()
            ->post(
                config("services.user_service.base_url") .
                    "/permissions/by-attachment-types",
                [
                    "user_id" => $user["id"],
                    "attachment_type_ids" => $attachmentTypeIds,
                ]
            );

        // Vérifier la réponse
        if ($response->successful()) {
            $permissions = collect($response->json());
            // Format attendu: [ attachment_type_id => ['view' => true, 'edit' => false, ...] ]

            // Fusionner les permissions dans chaque attachment
            $attachments = $attachments->map(function ($attachment) use (
                $permissions
            ) {
                $attachment["permissions"] =
                    $permissions[$attachment["attachment_type_id"]] ?? [];
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

        $user = request()->get("user");

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
                "slug" => $att["attachment_type_slug"],
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

    public function attachmentMapping(Document $linkedDocument)
    {
        $attachmentTypes = AttachmentType::pluck("id", "name")->toArray();

        //throw new Exception(json_encode($attachmentTypes), 1);

        // On charge tous les attachment_types une fois
        $attachmentTypes = AttachmentType::pluck("id", "name")->toArray();

        $documentAttachmentMap = [
            "invoice_provider" => [
                "attachment_type_name" => "Facture",
                "attachment_type_id" => $attachmentTypes["Facture"] ?? null,
            ],
            "order" => [
                "attachment_type_name" => "Bon de commande",
                "attachment_type_id" =>
                    $attachmentTypes["Bon de commande"] ?? null,
            ],
            "delivery_note" => [
                "attachment_type_name" => "Bon de livraison",
                "attachment_type_id" =>
                    $attachmentTypes["Bon de livraison"] ?? null,
            ],
            "payment" => [
                "attachment_type_name" => "Ordre de virement",
                "attachment_type_id" =>
                    $attachmentTypes["Ordre de virement"] ?? null,
            ],
            "treasury" => [
                "attachment_type_name" => "Attestation de règlement",
                "attachment_type_id" =>
                    $attachmentTypes["Attestation de règlement"] ?? null,
            ],
        ];

        //   $relationSlug = $linkedDocument->getTable(); // ou un champ slug dans ta BDD

        $specializedIds = [
            "invoice_provider" => InvoiceProvider::pluck(
                "document_id"
            )->toArray(),
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

        if (
            !empty($documentAttachmentMap[$relationSlug]["attachment_type_id"])
        ) {
            return $documentAttachmentMap[$relationSlug]["attachment_type_id"];
        }

        //throw new Exception(json_encode($relationSlug), 1);
    }

    public function notify_users(Request $request, $document)
    {
        $userServiceUrl =
            config("services.user_service.base_url") . "/by-permissions";

        $response = Http::withToken($request->bearerToken())
            ->acceptJson()
            ->get($userServiceUrl, [
                "actions" => ["be_notify"],
                "document_type_id" => $document->document_type->id,
                "folder_id" => $document->folder_id,
            ]);

        if ($response->failed()) {
            throw new \Exception(
                "Erreur lors de la récupération des utilisateurs autorisés : " .
                    $response->body()
            );
        }

        $users_to_notify = $response->json("data");

        $message = sprintf(
            "Bonjour,
Un nouveau courrier a été déposé dans votre espace documentaire\n. Objet: {$document->title} \n 
👉 Veuillez le consulter et, le cas échéant, effectuer les actions nécessaires."
        );

        // Récupérer juste les IDs
        $userIds = collect($users_to_notify)->pluck("id")->toArray();

        // Notifier en une seule requête
        return Http::withToken($request->bearerToken())->post(
            config("services.user_service.base_url") . "/mail-notifications",
            [
                "user_ids" => $userIds,
                "message" => $message,
                "document_id" => $document->id,
                "document_type_id" => $document->document_type->id,
            ]
        );
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
            $documentType = DocumentType::find($validated["document_type_id"]);

            // return ($user_connected);

            //on recupere le workflow

            if ($documentType->reception_mode == "AUTO_BY_ROLE") {
                $reference = $this->generateUniqueReference(6); // ex: longueur 6
                // Créer le document
                $document = Document::create([
                    "title" => $validated["titre"],
                    "document_type_id" => $validated["document_type_id"],
                    "department_id" => $validated["departement"] ?? null, // ✅ optionnel,
                    "workflow_id" => null,
                    "created_by" => $user_connected["id"], // si tu veux stocker l’utilisateur connecté
                    "created_at" => now(),
                    "updated_at" => now(),
                    "reference" => $reference,
                    "folder_id" => $validated["destination"] ?? null,
                    // autres champs génériques...
                ]);

                // Gestion du fichier uploadé
                if ($request->hasFile("courrier")) {
                    $this->handleUploadedFile(
                        $request,
                        $document,
                        $user_connected,
                        "courrier",
                        "autre"
                    );
                }

                $current_folder = Folder::find($validated["destination"]);

                if ($current_folder && $current_folder->notify_allowed_user) {
                    $this->notify_users($request, $document);
                }

                DB::commit();

                return response()->json(
                    [
                        "success" => true,
                        "message" =>
                            "Document créé avec succès et sans workflow",
                        "document" => $document,
                    ],
                    201
                );
            } else {
                // 🔹 Appel au microservice workflow
                $workflowServiceUrl = config(
                    "services.workflow_service.base_url"
                ); // ex: http://workflow-service/api
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


                $documentType = $document->document_type()->first(); // Objet avec class_name, relation_name et type

                $this->childHandler->handle(
                    $document,
                    $documentType,
                    $validated
                );

            

                // Si tu veux gérer des fichiers uploadés
                if ($request->hasFile("facture")) {
                    $document->save();
                    $this->handleUploadedFile(
                        $request,
                        $document,
                        $user_connected,
                        "facture",
                        "facture-originale"
                    );

                    
                }

                //Si la reference de l'engagement correspond a un document dans le systeme, on associe directement a la facture
                if (isset($validated["linkedDocument"])) {
                    $this->handleLinkedDocument(
                        $validated,
                        $document,
                        $user_connected
                    );

                   
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

                 //   return ["ok"];
                // return 
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
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function old_store(StoreDocumentRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $user_connected = $request->get("user");
            $documentType = DocumentType::findOrFail(
                $validated["document_type_id"]
            );

            // Création du document de base
            return $document = $this->createDocument(
                $validated,
                $user_connected,
                $documentType
            );

            // Gestion du fichier uploadé
            if ($request->hasFile("facture")) {
                $this->handleUploadedFile(
                    $request,
                    $document,
                    $user_connected,
                    "facture",
                    "facture-originale"
                );
            }

            // Gestion des documents liés
            if (isset($validated["linkedDocument"])) {
                $this->handleLinkedDocument(
                    $validated,
                    $document,
                    $user_connected
                );
            }

            // 4️⃣ Workflow selon le mode de réception
            $workflowInstance = $this->processWorkflow(
                $documentType,
                $validated,
                $document,
                $user_connected,
                $request
            );

            DB::commit();

            return response()->json(
                [
                    "success" => true,
                    "message" => "Document créé avec succès",
                    "document" => $document,
                    "workflow_instance" => $workflowInstance ?? null,
                ],
                201
            );
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function createDocument(
        array $validated,
        array $user_connected,
        DocumentType $documentType
    ): Document {
        $reference = $this->generateUniqueReference(6);

        $document = Document::create([
            "title" => $validated["titre"],
            "document_type_id" => $validated["document_type_id"],
            "department_id" => $validated["departement"] ?? null,
            "workflow_id" => null, // sera rempli par le workflow
            "created_by" => $user_connected["id"],
            "created_at" => now(),
            "updated_at" => now(),
            "reference" => $reference,
        ]);

        // Création d’une instance liée au document selon le type
        $className = $documentType->class_name;
        $relationName = $documentType->relation_name;

        if (class_exists($className) && $relationName) {
            $instance = new $className();
            $document->{$relationName}()->save($instance);
        }

        return $document;
    }

    private function processWorkflow(
        DocumentType $documentType,
        array $validated,
        Document $document,
        array $user_connected,
        $request
    ) {
        // Choix du traitement workflow selon le mode de réception
        if ($documentType->reception_mode === "AUTO_BY_ROLE") {
            $workflowInstance = $this->processAutoByRoleWorkflow(
                $validated,
                $document,
                $user_connected,
                $request
            );
        } else {
            $workflowInstance = $this->processManualWorkflow(
                $validated,
                $document,
                $user_connected,
                $request
            );
        }

        return $workflowInstance;
    }

    private function handleUploadedFile(
        $request,
        Document $document,
        array $user_connected,
        $input,
        $attachment_slug
    ) {
        $file = $request->file($input);

        $fileName = Str::random(20) . "_" . time() . "." . $file->extension();
        $type = $file->getClientMimeType();
        $size = $file->getSize();

        $file->move(
            storage_path("app/public/documents_attachments"),
            $fileName
        );

        $attachment = new Attachment();
        $attachment->document_id = $document->id;
        $attachment->is_main = true;
        $attachment->source = "UPLOAD";
        $attachment->created_by = $user_connected["id"];
        $attachment->attachment_type_id = AttachmentType::whereSlug(
            $attachment_slug
        )->first()->id;
        $attachment->save();

        $fileModel = new File();
        $fileModel->path = $fileName;
        $fileModel->type = $type;
        $fileModel->size = $size;

        $attachment->file()->save($fileModel);

        // Lancer le Job en arrière-plan
        GeneratePdfThumbnail::dispatch($attachment);
    }

    private function handleLinkedDocument(
        array $validated,
        Document $document,
        array $user_connected
    ) {
        $linkedDocument = Document::with([
            "main_attachment.file",
            "document_type",
        ])
            ->whereReference($validated["linkedDocument"])
            ->first();

        if (
            !$linkedDocument ||
            !$linkedDocument->main_attachment ||
            !$linkedDocument->main_attachment->file
        ) {
            throw new \Exception("Reference introuvable ou fichier manquant.");
        }

        $originalFile = $linkedDocument->main_attachment->file;
        $folder = "documents_attachments";

        $newFileName =
            Str::random(20) .
            "_" .
            time() .
            "." .
            pathinfo($originalFile->path, PATHINFO_EXTENSION);
        $newPath = $folder . "/" . $newFileName;
        $originalPath = $folder . "/" . $originalFile->path;

        Storage::disk("public")->copy($originalPath, $newPath);

        $newFile = new File();
        $newFile->path = $newFileName;
        $newFile->type = $originalFile->type;
        $newFile->size = $originalFile->size;

        $newAttachment = new Attachment();
        $newAttachment->is_main = false;
        $newAttachment->attachment_type_id = $this->attachmentMapping(
            $linkedDocument
        );
        $newAttachment->created_by = $user_connected["id"];

        $document->attachments()->save($newAttachment);
        $newAttachment->file()->save($newFile);
    }

    private function processAutoByRoleWorkflow(
        array $validated,
        Document $document,
        array $user_connected,
        $request
    ) {}

    private function processManualWorkflow(
        array $validated,
        Document $document,
        array $user_connected,
        $request
    ) {
        // Ici tu peux traiter les workflows manuels ou toute logique spécifique
        // Par exemple, créer une instance vide ou notifier des utilisateurs sans lancer d’instance automatique
        return null; // pas de workflow automatique
    }

  
        public function getDocumentsByIds(Request $request)
    {
        $DOC_CONFIG = config("document_types");
        
        $ids = $request->input("ids", []);
        $userId = $request->input("userId", null);
        $documentTypes = $request->input("documentTypes", []);
        $filters = $request->input("filters", []); // tableau associatif de filtres dynamiques

      

        $query = Document::query();

        // Filtre par IDs
        if (!empty($ids)) {
            $query->whereIn("id", $ids);
        }

        // Filtre par IDs
        // if (!empty($userId)) {
        //     $query->whereCreatedBy($userId);
        // }
        if (!empty($userId)) {
    
            $query->where(function ($q) use ($userId, $documentTypes) {

        // created_by (champ direct)
        $q->where('created_by', $userId);

        // requester (relation dynamique)
        $q->orWhereHas($documentTypes[0]/*->slug */, function ($qr) use ($userId) {
            $qr->where('beneficiary', $userId);
        });

    });
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
        if (!empty($filters["status"])) {
            $statuses = is_array($filters["status"])
                ? $filters["status"]
                : explode(",", $filters["status"]);
            $query->whereIn("status", $statuses);
        }

        // Filtre par type de prestataire
        if (!empty($filters["supplier_type"])) {
            //$statuses = is_array($filters['status']) ? $filters['status'] : explode(',', $filters['status']);
            $query->whereHas("invoice_provider." . $filters["supplier_type"]);
        }

        //   supplier_type


        // Filtre par fournisseur (via InvoiceProvider)
        if (!empty($filters["document_type_id"])) {
            $document_type_id = $filters["document_type_id"];
            $query->whereHas("document_type", function ($q) use (
                $document_type_id
            ) {
                $q->whereId($document_type_id); // ou le champ correct dans DocumentType
            });
        }

                // Filtre par montant dans InvoiceProvider
        if (!empty($filters["amount"])) {
            $query->whereHas("invoice_provider", function ($q) use ($filters) {
                switch ($filters["amount"]) {
                    case "lt_100k":
                        $q->where("amount", "<", 100000);
                        break;
                    case "100k_500k":
                        $q->whereBetween("amount", [100000, 500000]);
                        break;
                    case "gt_500k":
                        $q->where("amount", ">", 500000);
                        break;
                }
            });
        }

        // Filtre par fournisseur (via InvoiceProvider)
        if (!empty($filters["fournisseur_id"])) {
            $fournisseurId = $filters["fournisseur_id"];
            $query->whereHas("invoice_provider", function ($q) use (
                $fournisseurId
            ) {
                $q->where("id", $fournisseurId); // ou le champ correct dans InvoiceProvider
            });
        }

        if (!empty($filters["date_start"])) {
            $filters["date_start"] = Carbon::parse(
                $filters["date_start"]
            )->format("Y-m-d");
        }
        if (!empty($filters["date_end"])) {
            $filters["date_end"] = Carbon::parse($filters["date_end"])->format(
                "Y-m-d"
            );
        }

        // Filtre par date
        if (!empty($filters["date_start"]) && !empty($filters["date_end"])) {
            $query->whereBetween("created_at", [
                $filters["date_start"],
                $filters["date_end"],
            ]);
        } elseif (!empty($filters["date_start"])) {
            //  return ["ok"];
            $query->whereDate("created_at", ">=", $filters["date_start"]);
        } elseif (!empty($filters["date_end"])) {
            $query->whereDate("created_at", "<=", $filters["date_end"]);
        }

        // Charger les relations
        $query->with(array_merge(["document_type"], $documentTypes));

        // return $documents = $query->get();

        $documents = $query->get()->map(function ($doc) use ($documentTypes , $DOC_CONFIG) {

            // Détecter quel type de document est réellement présent
    $activeRelation = null;
    foreach ($documentTypes as $relation) {
        if ($doc->relationLoaded($relation) && $doc->$relation) {
            $activeRelation = $relation;
            break;
        }
    }

    // Base commune à tous les documents
    $base = [
        "id" => $doc->id,
        "title" => $doc->title,
        "document_type_name" => $doc->document_type->name,
        "document_type_id" => $doc->document_type_id,
        "type" => $doc->document_type->name,
        "status" => $doc->status,
        "created_at" => $doc->created_at,
        "created_by" => $doc->created_by,
    ];

    // Si aucun type trouvé → retourner juste la base
    if (!$activeRelation || !isset($DOC_CONFIG[$activeRelation])) {
        return $base;
    }

    $fields = $DOC_CONFIG[$activeRelation]["fields"];
    $relationObj = $doc->$activeRelation;

    // Injecter dynamiquement les champs configurés
    foreach ($fields as $responseKey => $modelField) {
        $value = $relationObj->$modelField ?? null;

          // Si la clé est susceptible de contenir un ID utilisateur
    $userKeys = ['demandeur', 'validateur', 'beneficiaire']; // Liste des clés à enrichir
    if (in_array($responseKey, $userKeys) && $value) {
        // Appel au microservice User pour récupérer les infos
        $response = Http:://withToken(config('services.user_service.token'))
            acceptJson()
            ->get(config('services.user_service.base_url') . "/{$value}");

    //new Exception(json_encode($response));

        if ($response->successful()) {
            $value = $response->json()['user']; // ou filtrer certaines infos, ex: ['id','name','email']
        }
        else{
   
            // new Exception(json_encode($response));

        }
    }

    //new Exception(json_encode($value));

    $base[$responseKey] = $value;
    }

    return $base;


            /*return [
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
            ];*/
        });

        return response()->json($documents);

    }

    public function enrichDocument($document , $token){

        if (in_array($document->document_type->slug ,["papier-taxi" , "note-de-frais" , "demande-d-absence" ])) {

                $slug = $document->document_type->slug;
    $relation = $this->documents_relation[$slug] ?? null;
         // Charger la relation dynamique
    if ($relation) {
        $document->load($relation);
    }

    // Récupérer l'entité
      $entity = $relation ? $document->$relation : null;

         // 🔹 Appel au microservice user
                $userServiceUrl = config(
                    "services.user_service.base_url"
                ); // ex: http://user-service/api
                $userResponse = Http::withToken($token)
                    ->acceptJson()
                    ->get(
                        "$userServiceUrl/".($entity->beneficiary > 0 ? $entity->beneficiary : 1)
                    );

                //dd($userResponse);

                $userData = null;
                if ($userResponse->ok()) {
                    $userData = $userResponse->json("user"); // récupère l'id du user

                // On attache les infos sans toucher à la DB
                $entity->beneficiary_details = $userData;
                $document->beneficiary = $userData['id'];

                //$document->relation = $entity;
                $document->setRelation($relation, $entity);

                } else {
                    $userResponse->json();
                }

            }

            return $document;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Misc\Document  $document
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request , Document $document)
    {
        $document->load("document_type");

        

         $document->load(
            $this->documents_relation[$document->document_type->slug],
            "attachments.file",
            "secondary_attachments"
        );


           // ######## DYNAMIQUE : enrichir beneficiary ########
        $document =  $this->enrichDocument($document , $request->bearerToken());

        return $document;
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
