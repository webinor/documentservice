<?php

namespace App\Services\TaxiPaper;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\Financial\TransactionInitiatorEnrichmentService;
use App\Services\UserServiceClient;
use Illuminate\Support\Collection;

class TaxiPaperDocumentEnrichmentHandler
    implements DocumentEnrichmentHandlerInterface
{
    protected UserServiceClient $userClient;

    protected TransactionInitiatorEnrichmentService $transactionInitiatorService;

    public function __construct(
        UserServiceClient $userClient,
        TransactionInitiatorEnrichmentService $transactionInitiatorService
    ) {
        $this->userClient =
            $userClient;

        $this->transactionInitiatorService =
            $transactionInitiatorService;
    }

    public function enrich(
        Document $document,
        array $base
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Actor / bénéficiaire
        |--------------------------------------------------------------------------
        */

        $document->actor_details =
            $this->userClient->resolveActor(
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

        $this->transactionInitiatorService
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

    /**
     * Enrichit plusieurs Taxi Papers dans le cadre d'un export.
     *
     * Cette méthode est distincte de enrich() car un export peut
     * contenir plusieurs documents du même type.
     *
     * L'objectif est d'éviter de refaire plusieurs fois les mêmes
     * appels externes pour les acteurs et les initiateurs.
     *
     * @param Collection $documents
     * @param array $bases
     *
     * @return Collection
     */
    public function enrichBatch(
        Collection $documents,
        array $bases = []
    ): Collection {

        /*
        |--------------------------------------------------------------------------
        | Préparation des acteurs
        |--------------------------------------------------------------------------
        |
        | Plusieurs Taxi Papers peuvent référencer le même acteur.
        |
        | On récupère donc d'abord les identifiants uniques afin
        | d'éviter de résoudre plusieurs fois le même acteur.
        |
        */

        $actorKeys = $documents
            ->map(function (Document $document) {
                return
                    $document->actor_type .
                    ':' .
                    $document->actor_id;
            })
            ->filter(function ($key) {
                return !empty($key);
            })
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Résolution des acteurs
        |--------------------------------------------------------------------------
        |
        | Cache local à cet export.
        |
        | Une même combinaison actor_type / actor_id ne sera donc
        | résolue qu'une seule fois pendant cet export.
        |
        */

        $actors = [];

        foreach ($actorKeys as $actorKey) {

            list(
                $actorType,
                $actorId
            ) = explode(':', $actorKey, 2);

            try {
                $actors[$actorKey] =
                    $this->userClient->resolveActor(
                        $actorType,
                        (int) $actorId
                    );
            } catch (\Throwable $e) {
                $actors[$actorKey] = null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Enrichissement des documents
        |--------------------------------------------------------------------------
        |
        | Les données communes ont maintenant été préparées.
        |
        | On peut donc parcourir les documents sans refaire les
        | résolutions d'acteurs déjà effectuées.
        |
        */

        return $documents
            ->values()
            ->map(function (
                Document $document
            ) use (
                $bases,
                $actors
            ) {

                $documentId =
                    (int) $document->id;


                /*
                |--------------------------------------------------------------------------
                | Base du document
                |--------------------------------------------------------------------------
                */

                $base =
                    isset($bases[$documentId])
                        && is_array($bases[$documentId])
                        ? $bases[$documentId]
                        : [];


                /*
                |--------------------------------------------------------------------------
                | Actor / bénéficiaire
                |--------------------------------------------------------------------------
                */

                $actorKey =
                    $document->actor_type .
                    ':' .
                    $document->actor_id;

                $document->actor_details =
                    $actors[$actorKey] ?? null;


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

                $this->transactionInitiatorService
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
            });
    }
}