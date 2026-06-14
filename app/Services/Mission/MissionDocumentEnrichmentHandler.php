<?php

namespace App\Services\Mission;


use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use Exception;
use Illuminate\Support\Facades\Http;

class MissionDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
    {
        
   

        $relation = $document->mission;

        $base['mission'] = optional($relation)->toArray();


        if (!$relation) {
            return $base;
        }

        // $base['mission'] = $relation->load('mission_expenses')->toArray();

        // exemple enrichissement spécifique
        $base['actor'] = $this->resolveActor($relation);

            // throw new Exception(json_encode($base['actor']), 1);


        return $base;
    }

    private function resolveActor($relation)
    {

    $baseUrl = config("services.user_service.base_url");
       
                            $response = Http::acceptJson()->get(
                                $baseUrl . "/{$relation->actor_id}"
                            );

                            if ($response->successful()) {
                             return   $value =
                                    $response->json()["user"] ??
                                    $response->json();
                            }
                        
        // logique métier spécifique TaxiPaper
        // return $relation->actor ?? null;
    }
}