<?php

namespace App\Services\Document;

use App\Services\UserServiceClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentEnricher{


protected UserServiceClient $user_service_client;

 private $documents_relation = [
        "facture-fournisseur-medical" => "invoice_provider.ledger_code",
        "facture-fournisseur-informatique" => "invoice_provider",
        "facture-note-honoraire" => "invoice_provider",
        "papier-taxi" => "taxi_paper",
        "note-de-frais" => "fee_note",
        "demande-d-absence" => "absence_request",
        "mission" => "mission.mission_expenses.expense_category",
        "demande-achat" => "purchase_request.purchase_request_items",

        "purchase-settlement" =>
            "purchase_settlement.purchase_settlement_items",
    ];

          public function __construct(
        UserServiceClient $user_service_client
    ) {
        $this->user_service_client = $user_service_client;
    }
                public function enrichDocument($document, $token)
    {
        $slug = $document->document_type->slug ?? null;




        $beneficiarySlugs = [
            "papier-taxi" => "beneficiary",
            "note-de-frais" => "beneficiary",
            "demande-d-absence" => "beneficiary",
        ];

        $actorSlugs = [
            "mission" => "actor_id",
            "demande-achat" => "requested_by",
        ];



        $relation = $this->documents_relation[$slug] ?? null;

        $main_relation = null;
        $secondary_relation = null;
        // Charger la relation dynamique
        if ($relation) {
            $relations = explode(".", $relation);
            $main_relation = $relations[0];
            $secondary_relation = $relations[1] ?? null;
            $third_relation = $relations[2] ?? null;
            $document->load($relation);
        }


        $document->transactions = $this->user_service_client->getDocumentTransactions($document->id);
        // $document->transactions = ["ok ok"];

        // throw new Exception(json_encode($document->transactions), 1);
        






        // Récupérer l'entité liée
        $entity = $main_relation ? $document->$main_relation : null;

        if (!$entity) {
            return $document;
        }

        $userServiceUrl = config("services.user_service.base_url");

        $userId = null;

        // =========================
        // 👤 CAS BENEFICIARY
        // =========================
        if (array_key_exists($slug, $beneficiarySlugs)) {
            // $userId = $entity->beneficiary ?? null;
            $userId = $entity->{$beneficiarySlugs[$slug]} ?? null;
        }



        // =========================
        // 🚀 CAS ACTOR (MISSION)
        // =========================
        if (array_key_exists($slug, $actorSlugs)) {
            $userId = $entity->{$actorSlugs[$slug]} ?? null;
        }

        // fallback sécurité
        $userId = $userId > 0 ? $userId : 0;

        // throw new Exception(json_encode($userId), 1);




        // =========================
        // 🔹 APPEL USER SERVICE
        // =========================
        $userResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(5)
            ->get("$userServiceUrl/{$userId}");

        if ($userResponse->ok()) {
            $userData = $userResponse->json("user");

        // throw new Exception(json_encode($userData['department_data']["manager_id"]), 1);


        // throw new Exception(json_encode($userData['department_data']), 1);


        if ($userData['department_data'] && $userData['department_data']["manager_id"]) {




        $managerResponse = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->get("$userServiceUrl/{$userData['department_data']['manager_id']}");



            $managerData = $managerResponse->json("user");

            $userData["manager"]= $managerData;
                        
            
        // throw new \Exception(json_encode($managerData), 1);

        
        }


            // Attachement sans persistance DB
            $entityKey = in_array($slug, $actorSlugs)
                ? "actor_details"
                : // : 'beneficiary_details';
                "actor_details";

            $entity->{$entityKey} = $userData;

        throw new \Exception(json_encode("yeahhhhhhhhhhhhhhh"), 1);


            if ($secondary_relation && $third_relation) {
                $entity->load("{$secondary_relation}.{$third_relation}");
            } elseif ($secondary_relation) {
                $entity->load($secondary_relation);
            }

            // optionnel : normalisation ID
            if ($entityKey === "beneficiary_details") {
                // $document->beneficiary = $userData['id'] ?? null;
                $document->actor = $userData["id"] ?? null;
            } else {
                $document->actor = $userData["id"] ?? null;
            }

            // Log::info($document->mission->toJson());

            $document->setRelation($main_relation, $entity);

            // Log::info($document->mission->toJson());
        } else {
            // log silencieux (important en prod)
            Log::warning("User service failed", [
                "slug" => $slug,
                "status" => $userResponse->status(),
                "body" => $userResponse->body(),
            ]);
        }

        return $document;
    }
}