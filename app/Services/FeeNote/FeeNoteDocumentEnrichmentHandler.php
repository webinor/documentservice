<?php

namespace App\Services\FeeNote;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\Financial\TransactionInitiatorEnrichmentService;
use App\Services\UserServiceClient;

class FeeNoteDocumentEnrichmentHandler
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
        | La note de frais fonctionne en ONE_SHOT.
        |
        | Sa transaction de référence est :
        |
        | FEE_NOTE_SETTLEMENT
        |
        */

        app(TransactionInitiatorEnrichmentService::class)
            ->enrichDocument(
                $document,
                [
                    'transaction_initiator_details' =>
                        'FEE_NOTE_SETTLEMENT',
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