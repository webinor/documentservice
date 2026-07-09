<?php

namespace App\Services\Absence;


use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;

class AbsenceDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
    {

       
        $userClient = new UserServiceClient();

        $actor_details = $userClient->resolveActor(
        $document->actor_type,
        $document->actor_id
    );

    $document->actor_details = $actor_details;

     
        return $document->toArray();
    }

   
}