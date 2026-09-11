<?php

namespace App\Services\Financial;

use App\Services\UserServiceClient;
use Illuminate\Support\Collection;

class TransactionInitiatorEnrichmentService
{
    protected UserServiceClient $userClient;

    public function __construct(
        UserServiceClient $userClient
    ) {
        $this->userClient = $userClient;
    }


    /**
     * Enrichit toutes les transactions d'un document
     * avec les informations de leur initiateur.
     *
     * Chaque transaction peut contenir :
     *
     * initiated_by
     *
     * Le service ajoute automatiquement :
     *
     * initiator_details
     *
     * Exemple :
     *
     * [
     *     'transaction_type_code' => 'REGULARIZATION_ADVANCE',
     *     'initiated_by' => 11,
     *     'initiator_details' => [
     *         ...
     *     ]
     * ]
     */
    public function enrichTransactions(
        $transactions
    ): Collection {

        $transactions = collect($transactions);


        /*
        |--------------------------------------------------------------------------
        | Recherche des IDs des initiateurs
        |--------------------------------------------------------------------------
        |
        | On récupère tous les initiated_by présents dans les transactions.
        |
        | unique() permet d'éviter de rechercher plusieurs fois le même
        | utilisateur.
        |
        | Exemple :
        |
        | Advance     → initiated_by = 11
        | Settlement  → initiated_by = 11
        |
        | L'utilisateur 11 ne sera chargé qu'une seule fois.
        |
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
        | Chargement des initiateurs
        |--------------------------------------------------------------------------
        |
        | On construit un tableau indexé par l'ID utilisateur.
        |
        | Exemple :
        |
        | $initiators[11] = {...}
        | $initiators[15] = {...}
        |
        */

        $initiators = [];

        foreach ($initiatorIds as $initiatorId) {

            try {

                $initiators[$initiatorId] =
                    $this->userClient->resolveActor(
                        'USER',
                        $initiatorId
                    );

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Une erreur sur un utilisateur ne doit pas empêcher
                | l'enrichissement complet du document.
                |--------------------------------------------------------------------------
                */

                $initiators[$initiatorId] = null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Enrichissement des transactions
        |--------------------------------------------------------------------------
        |
        | Chaque transaction reçoit :
        |
        | initiator_details
        |
        */

        return $transactions
            ->map(function ($transaction) use ($initiators) {

                $initiatedBy =
                    $transaction['initiated_by'] ?? null;


                /*
                |--------------------------------------------------------------------------
                | Initialisation
                |--------------------------------------------------------------------------
                */

                $transaction['initiator_details'] = null;


                /*
                |--------------------------------------------------------------------------
                | Résolution de l'initiateur
                |--------------------------------------------------------------------------
                */

                if (!empty($initiatedBy)) {

                    $transaction['initiator_details'] =
                        $initiators[$initiatedBy] ?? null;
                }


                return $transaction;
            })
            ->values();
    }


    /**
     * Retourne les informations de l'initiateur
     * d'une transaction précise.
     *
     * Exemple :
     *
     * getInitiatorDetails(
     *     $transactions,
     *     'REGULARIZATION_ADVANCE'
     * );
     *
     * Retourne directement :
     *
     * [
     *     ...
     * ]
     *
     * ou null si la transaction n'existe pas.
     */
    public function getInitiatorDetails(
        $transactions,
        string $transactionTypeCode
    ) {

        /*
        |--------------------------------------------------------------------------
        | Les transactions doivent d'abord être enrichies
        |--------------------------------------------------------------------------
        */

        $transactions = $this->enrichTransactions(
            $transactions
        );


        /*
        |--------------------------------------------------------------------------
        | Recherche de la transaction demandée
        |--------------------------------------------------------------------------
        */

        $transaction = $transactions
            ->first(function ($transaction) use (
                $transactionTypeCode
            ) {

                return (
                    ($transaction['transaction_type_code'] ?? null)
                    === $transactionTypeCode
                );
            });


        /*
        |--------------------------------------------------------------------------
        | Transaction inexistante
        |--------------------------------------------------------------------------
        */

        if (!$transaction) {
            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Retour de l'initiateur
        |--------------------------------------------------------------------------
        */

        return $transaction['initiator_details'] ?? null;
    }


    /**
     * Enrichit les transactions d'un document et ajoute
     * plusieurs champs calculés sur le document.
     *
     * Cette méthode est particulièrement utile pour les documents
     * ayant plusieurs types de transactions, comme la fiche
     * à régulariser.
     *
     * Exemple de configuration :
     *
     * [
     *     'advance_initiator_details' =>
     *         'REGULARIZATION_ADVANCE',
     *
     *     'settlement_initiator_details' =>
     *         'REGULARIZATION_SETTLEMENT',
     * ]
     */
    public function enrichDocument(
        $document,
        array $transactionFields
    ) {

        /*
        |--------------------------------------------------------------------------
        | Récupération des transactions
        |--------------------------------------------------------------------------
        */

        $transactions = collect(
            $document->transactions
        );


        /*
        |--------------------------------------------------------------------------
        | Enrichissement des transactions
        |--------------------------------------------------------------------------
        */

        $document->transactions =
            $this->enrichTransactions(
                $transactions
            );


        /*
        |--------------------------------------------------------------------------
        | Initialisation des champs calculés
        |--------------------------------------------------------------------------
        |
        | On initialise toujours les champs à null.
        |
        | Ainsi, même si une transaction n'existe pas,
        | la structure JSON reste stable.
        |
        */

        foreach ($transactionFields as $field => $transactionTypeCode) {

            $document->{$field} = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Recherche des initiateurs demandés
        |--------------------------------------------------------------------------
        */

        foreach ($transactionFields as $field => $transactionTypeCode) {

            $transaction = $document->transactions
                ->first(function ($transaction) use (
                    $transactionTypeCode
                ) {

                    return (
                        ($transaction['transaction_type_code'] ?? null)
                        === $transactionTypeCode
                    );
                });


            /*
            |--------------------------------------------------------------------------
            | Affectation de l'initiateur
            |--------------------------------------------------------------------------
            */

            if ($transaction) {

                $document->{$field} =
                    $transaction['initiator_details'] ?? null;
            }
        }


        return $document;
    }
}