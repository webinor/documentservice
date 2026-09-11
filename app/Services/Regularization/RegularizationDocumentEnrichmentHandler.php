<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\Financial\TransactionInitiatorEnrichmentService;
use App\Services\UserServiceClient;

class RegularizationDocumentEnrichmentHandler
{
    public function enrich(
        Document $document,
        array $base
    ): array {

        $userClient = new UserServiceClient();


        /*
        |--------------------------------------------------------------------------
        | Chargement des relations nécessaires
        |--------------------------------------------------------------------------
        */

        $document->load([
            'regularization_sheet.items',
            'signature_positions'
        ]);


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
        | Créateur du document
        |--------------------------------------------------------------------------
        |
        | Le créateur du document est toujours un EMPLOYEE.
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

        $document->dynamic_amount = 0;
        $document->actual_amount = 0;

        if (
            $document->regularization_sheet &&
            $document->regularization_sheet->items
        ) {

            $items =
                $document->regularization_sheet->items;


            $document->dynamic_amount =
                $items->sum(function ($item) {

                    return
                        ($item->planned_amount ?? 0)
                        *
                        ($item->planned_quantity ?? 1);
                });


            $document->actual_amount =
                $items->sum(function ($item) {

                    return
                        ($item->actual_amount ?? 0)
                        *
                        ($item->actual_quantity ?? 1);
                });
        }


        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        |
        | La fiche à régulariser possède deux transactions
        | financières principales :
        |
        | REGULARIZATION_ADVANCE
        | REGULARIZATION_SETTLEMENT
        |
        | Le service générique va :
        |
        | 1. rechercher les initiated_by ;
        | 2. charger les utilisateurs ;
        | 3. ajouter initiator_details à chaque transaction ;
        | 4. extraire l'initiateur de l'avance ;
        | 5. extraire l'initiateur du settlement.
        |
        */

        app(TransactionInitiatorEnrichmentService::class)
            ->enrichDocument(
                $document,
                [
                    'advance_initiator_details' =>
                        'REGULARIZATION_ADVANCE',

                    'settlement_initiator_details' =>
                        'REGULARIZATION_SETTLEMENT',
                ]
            );


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
        | Résultat final
        |--------------------------------------------------------------------------
        */

        return $document->toArray();
    }
}