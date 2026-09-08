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
            'signature_positions'
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
        | Le créateur du document est toujours un EMPLOYEE.
        | Son identifiant est stocké dans creator_employee_id.
        |
        */

        $document->creator_details = null;

        if (!empty($document->creator_employee_id)) {
            $document->creator_details = $userClient->resolveActor(
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
    $items = $document->regularization_sheet->items;

    $document->dynamic_amount = $items->sum(function ($item) {
        return ($item->planned_amount ?? 0) * ($item->planned_quantity ?? 1);
    });

    $document->actual_amount = $items->sum(function ($item) {
        return ($item->actual_amount ?? 0) * ($item->actual_quantity ?? 1);
    });
}

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        |
        | Nous recherchons les initiateurs de chaque transaction.
        |
        | Exemple :
        |
        | REGULARIZATION_ADVANCE
        | initiated_by = 11
        |
        | REGULARIZATION_SETTLEMENT
        | initiated_by = 15
        |
        | Chaque transaction recevra :
        |
        | "initiator_details": {...}
        |
        */

        $transactions = collect($document->transactions);

        /*
        |--------------------------------------------------------------------------
        | Initialisation des initiateurs Advance / Settlement
        |--------------------------------------------------------------------------
        */

        $document->advance_initiator_details = null;
        $document->settlement_initiator_details = null;

        /*
        |--------------------------------------------------------------------------
        | Recherche des IDs des initiateurs
        |--------------------------------------------------------------------------
        */

        $initiatorIds = $transactions
            ->pluck('initiated_by')
            ->filter(function ($id) {
                return !empty($id);
            })
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Chargement des utilisateurs
        |--------------------------------------------------------------------------
        |
        | On ne fait qu'une requête par utilisateur.
        |
        | Si Advance et Settlement ont le même initiated_by,
        | l'utilisateur ne sera recherché qu'une seule fois.
        |
        */

        $initiators = [];

        foreach ($initiatorIds as $initiatorId) {
            try {
                $initiators[$initiatorId] =
                    $userClient->resolveActor(
                        'USER',
                        $initiatorId
                    );
            } catch (\Throwable $e) {
                $initiators[$initiatorId] = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Enrichissement des transactions
        |--------------------------------------------------------------------------
        */

        $document->transactions = $transactions
            ->map(function ($transaction) use ($initiators) {

                $initiatedBy = $transaction['initiated_by'];

                /*
                |--------------------------------------------------------------------------
                | Initiateur de la transaction
                |--------------------------------------------------------------------------
                */

                $transaction['initiator_details'] = null;

                if (!empty($initiatedBy)) {
                    $transaction['initiator_details'] =
                        $initiators[$initiatedBy] ?? null;
                }

                return $transaction;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Initiateur de l'Advance
        |--------------------------------------------------------------------------
        |
        | On recherche spécifiquement la transaction :
        |
        | REGULARIZATION_ADVANCE
        |
        */

        $advanceTransaction = $document->transactions
            ->first(function ($transaction) {

                return $transaction['transaction_type_code'] === 'REGULARIZATION_ADVANCE';
            });

        if ($advanceTransaction) {
            $document['advance_initiator_details'] =
                $advanceTransaction['initiator_details'];
        }

        /*
        |--------------------------------------------------------------------------
        | Initiateur du Settlement
        |--------------------------------------------------------------------------
        |
        | On recherche spécifiquement la transaction :
        |
        | REGULARIZATION_SETTLEMENT
        |
        */

        $settlementTransaction = $document->transactions
            ->first(function ($transaction) {

                return $transaction['transaction_type_code'] === 'REGULARIZATION_SETTLEMENT';
            });

        if ($settlementTransaction) {
            $document['settlement_initiator_details'] =
                $settlementTransaction['initiator_details'];
        }

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