<?php

namespace App\Services\FeeNote;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use Exception;
use Illuminate\Support\Facades\Http;

class FeeNoteDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
    {
        $document->load('fee_note');

        $relation = $document->fee_note;

        if (!$relation) {
            return $base;
        }

        // $base['mission'] = $relation->load('mission_expenses')->toArray();

        // exemple enrichissement spécifique
        $base['beneficiary'] = $this->resolveActor($relation);

            // throw new Exception(json_encode($base['beneficiary']), 1);


        return $base;
    }

    private function resolveActor($relation)
    {

    $baseUrl = config("services.user_service.base_url");
       
                            $response = Http::acceptJson()->get(
                                $baseUrl . "/{$relation->beneficiary}"
                            );

                            if ($response->successful()) {
                             return   $value =
                                    $response->json()["user"] ??
                                    $response->json();
                            }
                        
        // logique métier spécifique FeeNote
        // return $relation->actor ?? null;
    }
}