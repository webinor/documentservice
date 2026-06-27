<?php

namespace App\Services\FeeNote;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Facades\Http;

class FeeNoteDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
    {
        // $document->load('fee_note'); //relation deja chargee par le manager

       
        $userClient = new UserServiceClient();

        $actor_details = $userClient->resolveActor(
        $document->actor_type,
        $document->actor_id
    );

    $document->actor_details = $actor_details;

     
        return $document->toArray();
    }

   
}