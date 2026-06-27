<?php

namespace App\Services\TaxiPaper;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Facades\Http;

class TaxiPaperDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
{
    // $document->load('taxi_paperss');
    // $relationName = $document->document_type->relation_name;

    // if ($relationName) {
    //     $document->load($relationName);

    //     $relationData = $document->{$relationName} ?? null;

    //     $base[$relationName] = $relationData
    //         ? $relationData->toArray()
    //         : null;
    // }

    $userClient = new UserServiceClient();

    $actor_details = $userClient->resolveActor(
        $document->actor_type,
        $document->actor_id
    );

    // $base['actor_details'] = $actor_details;
    $document->actor_details = $actor_details;

    

    // throw new Exception(json_encode($actor_details), 1);
    

    return $document->toArray();
}


}