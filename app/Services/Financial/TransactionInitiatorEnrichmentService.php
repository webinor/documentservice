<?php

namespace App\Services\Financial;

use App\Services\UserServiceClient;
use Illuminate\Support\Collection;

class TransactionInitiatorEnrichmentService
{
    protected UserServiceClient $userClient;

    /**
     * Cache des initiateurs déjà résolus.
     *
     * Clé :
     * user_id
     *
     * Valeur :
     * informations de l'utilisateur
     *
     * Ce cache permet d'éviter de refaire plusieurs appels
     * HTTP vers le UserService lorsqu'un même utilisateur
     * est l'initiateur de plusieurs transactions ou de
     * plusieurs documents.
     *
     * Exemple :
     *
     * Document A
     *     → transaction 1 → initiated_by = 11
     *
     * Document B
     *     → transaction 2 → initiated_by = 11
     *
     * L'utilisateur 11 ne sera chargé qu'une seule fois.
     *
     * @var array<int, array|null>
     */
    protected $initiatorCache = [];

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
     *
     * Le service utilise un cache interne afin d'éviter
     * de rechercher plusieurs fois le même utilisateur.
     */
    public function enrichTransactions(
        $transactions
    ): Collection {

        $transactions = collect(
            $transactions
        );


        /*
        |--------------------------------------------------------------------------
        | Recherche des IDs des initiateurs
        |--------------------------------------------------------------------------
        |
        | On récupère tous les initiated_by présents dans les transactions.
        |
        | filter() permet d'ignorer les transactions qui ne possèdent
        | pas d'initiateur.
        |
        | map() permet de normaliser les IDs en entier.
        |
        | unique() permet d'éviter de rechercher plusieurs fois
        | le même utilisateur.
        |
        | Exemple :
        |
        | Advance     → initiated_by = 11
        | Settlement  → initiated_by = 11
        | Autre       → initiated_by = 15
        |
        | Les utilisateurs 11 et 15 ne seront chargés qu'une seule fois.
        |
        */

        $initiatorIds = $transactions
            ->pluck('initiated_by')
            ->filter(function ($id) {
                return !empty($id);
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Chargement des initiateurs
        |--------------------------------------------------------------------------
        |
        | On charge uniquement les utilisateurs qui ne sont pas
        | déjà présents dans le cache.
        |
        | Cela permet d'éviter de refaire un appel HTTP lorsqu'un
        | même initiateur apparaît dans plusieurs transactions
        | ou dans plusieurs documents traités par le même service.
        |
        */

        foreach ($initiatorIds as $initiatorId) {

            /*
            |--------------------------------------------------------------------------
            | Initiateur déjà chargé
            |--------------------------------------------------------------------------
            |
            | array_key_exists() est utilisé volontairement plutôt
            | que isset(), car une valeur null peut également être
            | présente dans le cache.
            |
            | Exemple :
            |
            | $initiatorCache[11] = null
            |
            | signifie que l'utilisateur 11 a déjà été recherché,
            | mais que UserService n'a pas retourné de données.
            |
            */

            if (array_key_exists(
                $initiatorId,
                $this->initiatorCache
            )) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Résolution de l'utilisateur
            |--------------------------------------------------------------------------
            |
            | Les initiateurs de transactions sont des USER.
            |
            */

            try {

                $this->initiatorCache[$initiatorId] =
                    $this->userClient->resolveActor(
                        'USER',
                        $initiatorId
                    );

            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | Gestion d'erreur
                |--------------------------------------------------------------------------
                |
                | Une erreur lors de la résolution d'un utilisateur
                | ne doit pas empêcher l'enrichissement complet
                | des transactions.
                |
                | On mémorise également null dans le cache afin
                | de ne pas refaire inutilement le même appel HTTP
                | plus tard.
                |
                */

                $this->initiatorCache[$initiatorId] =
                    null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Enrichissement des transactions
        |--------------------------------------------------------------------------
        |
        | À ce stade, toutes les informations disponibles sont déjà
        | présentes en mémoire.
        |
        | Aucun nouvel appel HTTP n'est effectué dans cette partie.
        |
        | Chaque transaction reçoit :
        |
        | initiator_details
        |
        */

        return $transactions
            ->map(function ($transaction) {

                $initiatedBy =
                    isset(
                        $transaction['initiated_by']
                    )
                        ? (int) $transaction['initiated_by']
                        : null;


                /*
                |--------------------------------------------------------------------------
                | Initialisation de l'initiateur
                |--------------------------------------------------------------------------
                */

                $transaction['initiator_details'] =
                    $initiatedBy
                        ? (
                            $this->initiatorCache[
                                $initiatedBy
                            ] ?? null
                        )
                        : null;


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

        $transactions =
            $this->enrichTransactions(
                $transactions
            );


        /*
        |--------------------------------------------------------------------------
        | Recherche de la transaction demandée
        |--------------------------------------------------------------------------
        */

        $transaction =
            $transactions->first(
                function ($transaction) use (
                    $transactionTypeCode
                ) {

                    return (
                        ($transaction[
                            'transaction_type_code'
                        ] ?? null)
                        === $transactionTypeCode
                    );
                }
            );


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

        return $transaction[
            'initiator_details'
        ] ?? null;
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

        $transactions =
            collect(
                $document->transactions
            );


        /*
        |--------------------------------------------------------------------------
        | Enrichissement des transactions
        |--------------------------------------------------------------------------
        |
        | Les transactions sont enrichies une seule fois.
        |
        | Les informations des initiateurs déjà chargés sont
        | récupérées depuis le cache interne.
        |
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

        foreach (
            $transactionFields
            as $field => $transactionTypeCode
        ) {

            $document->{$field} =
                null;
        }


        /*
        |--------------------------------------------------------------------------
        | Recherche des initiateurs demandés
        |--------------------------------------------------------------------------
        |
        | Chaque champ correspond à un type de transaction.
        |
        | Exemple :
        |
        | advance_initiator_details
        |     → REGULARIZATION_ADVANCE
        |
        | settlement_initiator_details
        |     → REGULARIZATION_SETTLEMENT
        |
        */

        foreach (
            $transactionFields
            as $field => $transactionTypeCode
        ) {

            $transaction =
                $document->transactions
                    ->first(
                        function ($transaction) use (
                            $transactionTypeCode
                        ) {

                            return (
                                ($transaction[
                                    'transaction_type_code'
                                ] ?? null)
                                === $transactionTypeCode
                            );
                        }
                    );


            /*
            |--------------------------------------------------------------------------
            | Affectation de l'initiateur
            |--------------------------------------------------------------------------
            */

            if ($transaction) {

                $document->{$field} =
                    $transaction[
                        'initiator_details'
                    ] ?? null;
            }
        }


        return $document;
    }
}
