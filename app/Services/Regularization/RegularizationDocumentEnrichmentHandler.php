<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;

class RegularizationDocumentEnrichmentHandler
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
