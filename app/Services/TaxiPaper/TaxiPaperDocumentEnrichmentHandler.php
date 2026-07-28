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


    $userClient = new UserServiceClient();

    $actor_details = $userClient->resolveActor(
        $document->actor_type,
        $document->actor_id
    );

    // $base['actor_details'] = $actor_details;
    $document->actor_details = $actor_details;    

    return $document->toArray();
}


}