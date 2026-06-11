<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use Illuminate\Support\Facades\Http;

class LegacyDocumentEnricher
{
    public function enrich(Document $doc, array $base , array $documentTypes): array
    {
        // $documentTypes = config('document.types');
        $DOC_CONFIG = config("document_types");


        $activeRelation = null;

        foreach ($documentTypes as $relation) {
            if ($doc->relationLoaded($relation) && $doc->$relation) {
                $activeRelation = $relation;
                break;
            }
        }

        if (!$activeRelation || !isset($DOC_CONFIG[$activeRelation])) {
            return $base;
        }

        $fields = $DOC_CONFIG[$activeRelation]["fields"];
        $relationObj = $doc->$activeRelation;

        foreach ($fields as $responseKey => $modelField) {
                //prestataire
                $value = $relationObj->$modelField ?? null;

                if (array_key_exists($modelField, $base)) {
                    // throw new Exception(json_encode($modelField), 1);
                    continue;
                }

                $userKeys = [
                    "demandeur",
                    "validateur",
                    "beneficiaire",
                    "actor_type",
                ];
                $providerKeys = ["prestataire"]; // Liste des clés à enrichir

                    // throw new Exception("-- $id --", 1);


                if (in_array($responseKey, $userKeys) && $value) {
                    // 👉 récupération standardisée
                    $type = $value; // $filters['actor_type'] ?? null;
                    $id = $relationObj->actor_id ?? null;
                    // throw new Exception("-- $id --", 1);

                    if ($id && $type) {
                        // $missions_with_expenses = $relationObj->load('missions_expenses');
                        $relationObj->load("mission_expenses");

                        $mission = $relationObj->toArray();

                        $base["mission"] = $mission;

                        // $doc->load("mission.missions_expenses");

                        $baseUrl = null;

                        if ($type === "INTERNAL") {
                            $baseUrl = config("services.user_service.base_url");
                        } elseif ($type === "EXTERNAL") {
                            $baseUrl = config(
                                "services.external_actor_service.base_url"
                            );
                        }

                        if ($baseUrl) {
                            $response = Http::acceptJson()->get(
                                $baseUrl . "/{$id}"
                            );

                            if ($response->successful()) {
                                $value =
                                    $response->json()["user"] ??
                                    $response->json();
                            }
                        }
                    }
                }

                if (in_array($responseKey, $providerKeys) && $value) {
                    // Appel au microservice User pour récupérer les infos
                    $response = Http::acceptJson() //withToken(config('services.user_service.token'))
                        ->get(
                            config("services.supplier_service.base_url") .
                                "/{$value}"
                        );

                    //new Exception(json_encode($response));

                    if ($response->successful()) {
                        $value = $response->json()["data"]; // ou filtrer certaines infos, ex: ['id','name','email']
                    } else {
                        // new Exception(json_encode($response));
                    }
                }

                //new Exception(json_encode($value));

                $base[
                    $responseKey == "actor_type" ? "actor" : $responseKey
                ] = $value;

                // $base[$responseKey] = $responseKey === 'amount'
                // ? number_format($value, 0, ',', '.')
                // : $value;
            }

        return $base;
    }
}