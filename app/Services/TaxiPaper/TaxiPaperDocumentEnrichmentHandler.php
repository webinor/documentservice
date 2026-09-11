<?php

namespace App\Services\TaxiPaper;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\Financial\TransactionInitiatorEnrichmentService;
use App\Services\UserServiceClient;

class TaxiPaperDocumentEnrichmentHandler
    implements DocumentEnrichmentHandlerInterface
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

        $document->actor_details =
            $userClient->resolveActor(
                $document->actor_type,
                $document->actor_id
            );


        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        |
        | Le Taxi Paper fonctionne en ONE_SHOT.
        |
        | Sa transaction financière de référence est :
        |
        | TAXI_PAPER_SETTLEMENT
        |
        | L'initiateur de cette transaction devient :
        |
        | transaction_initiator_details
        |
        */

        app(TransactionInitiatorEnrichmentService::class)
            ->enrichDocument(
                $document,
                [
                    'transaction_initiator_details' =>
                        'TAXI_PAPER_SETTLEMENT',
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | Résultat final
        |--------------------------------------------------------------------------
        */

        return $document->toArray();
    }
}