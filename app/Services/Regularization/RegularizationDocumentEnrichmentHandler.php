<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;

class RegularizationDocumentEnrichmentHandler
{
    public function enrich(Document $document, array $base): array
    {
        $userClient = new UserServiceClient();

        /*
        |--------------------------------------------------------------------------
        | Chargement des relations nécessaires
        |--------------------------------------------------------------------------
        */

        $document->load([
            'regularization_sheet.items',
            'signature_positions',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Actor / bénéficiaire
        |--------------------------------------------------------------------------
        */

        $document->actor_details = $userClient->resolveActor(
            $document->actor_type,
            $document->actor_id
        );

        /*
        |--------------------------------------------------------------------------
        | Montant dynamique
        |--------------------------------------------------------------------------
        */

        $document->dynamic_amount =
            $document->regularization_sheet
                ->items
                ->sum('planned_amount');

        /*
        |--------------------------------------------------------------------------
        | Positions de signature
        |--------------------------------------------------------------------------
        |
        | On expose explicitement les positions afin que le frontend
        | et les requirements puissent déterminer si un utilisateur
        | possède déjà une position de signature.
        |
        */

        $document->signature_positions =
            $document->signature_positions
                ->values()
                ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Résultat
        |--------------------------------------------------------------------------
        */

        return $document->toArray();
    }
}