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
        | Créateur du document
        |--------------------------------------------------------------------------
        |
        | Le créateur est toujours un EMPLOYEE.
        | Son identifiant est stocké dans creator_employee_id.
        |
        */

        $document->creator_details = null;

        if (!empty($document->creator_employee_id)) {

            $document->creator_details =
                $userClient->resolveActor(
                    'EMPLOYEE',
                    $document->creator_employee_id
                );
        }

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