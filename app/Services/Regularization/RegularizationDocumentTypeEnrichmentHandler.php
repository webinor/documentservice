<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\Financial\TransactionInitiatorEnrichmentService;
use App\Services\UserServiceClient;

class RegularizationDocumentTypeEnrichmentHandler
{
    public function enrich(
        Document $document,
        array $base
    ): array {

        $userClient = new UserServiceClient();




         /*
        |--------------------------------------------------------------------------
        | Actor / bénéficiaire
        |--------------------------------------------------------------------------
        */

        $base['actor_details'] =
            $userClient->resolveActor(
                $document->actor_type,
                $document->actor_id
            );


        /*
        |--------------------------------------------------------------------------
        | Chargement des relations nécessaires
        |--------------------------------------------------------------------------
        */

        $document->load([
            'regularization_sheet'
        ]);

        $base['child_type'] = $document->regularization_sheet->regularization_type;
        $base['child_mode'] = $document->regularization_sheet->regularization_mode;


        

        /*
        |--------------------------------------------------------------------------
        | Résultat final
        |--------------------------------------------------------------------------
        */

        return $base;// $document->toArray();
    }
}