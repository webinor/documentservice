<?php

namespace App\Managers;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Collection;

class DocumentEnrichmentManager
{
    protected UserServiceClient $user_service_client;

    private $documents_relation = [
        "facture-fournisseur-medical" =>
            "invoice_provider.ledger_code",

        "facture-fournisseur-informatique" =>
            "invoice_provider",

        "facture-note-honoraire" =>
            "invoice_provider",

        "papier-taxi" =>
            "taxi_paper",

        "note-de-frais" =>
            "fee_note",

        "demande-d-absence" =>
            "absence_request",

        "mission" =>
            "mission.mission_expenses.expense_category",

        "demande-achat" =>
            "purchase_request.purchase_request_items",

        "purchase-settlement" =>
            "purchase_settlement.purchase_settlement_items",
    ];

    public function __construct(
        UserServiceClient $user_service_client
    ) {
        $this->user_service_client =
            $user_service_client;
    }

    /**
     * Construit les données de base d'un document.
     *
     * @param Document $doc
     *
     * @return array
     */
    public function getBase(Document $doc): array
    {
        return [
            "id" => $doc->id,
            "code" => $doc->code,
            "amount" => $doc->dynamic_amount,
            "title" => $doc->title,
            "date_due" => $doc->date_due,
            "document_type" => $doc->document_type,
            "document_type_name" =>
                $doc->document_type->name,
            "document_type_slug" =>
                $doc->document_type->slug,
            "document_type_id" =>
                $doc->document_type_id,
            "type" =>
                $doc->document_type->name,
            "status" => $doc->status,
            "created_at" => $doc->created_at,
            "created_by" => $doc->created_by,
            "actor_type" => $doc->actor_type,
            "actor_id" => $doc->actor_id,
        ];
    }

    /**
     * Enrichissement individuel.
     *
     * Cette méthode est volontairement conservée telle quelle
     * afin de ne pas modifier le comportement existant du
     * document-service.
     *
     * @param Document $document
     * @param mixed|null $b
     *
     * @return array
     */
    public function enrich(
        Document $document,
        $b = null
    ): array {
        $base = !$b
            ? $this->getBase($document)
            : $b;

        $type = $document->document_type;

        $handlerClass =
            $type->enrichment_handler_class;

        if (!$handlerClass) {
            throw new \Exception(
                "Aucun handler d'enrichissement configuré " .
                "pour le type de document '{$type->name}'"
            );
        }

        /*
         * Chargement de la relation métier.
         */
        $relationName =
            $document->document_type->relation_name;

        if ($relationName) {
            $document->load($relationName);

            $relationData =
                $document->{$relationName} ?? null;

            $base[$relationName] =
                $relationData
                    ? $relationData->toArray()
                    : null;
        }

        /*
         * Transactions du document.
         */
        $transactions =
            $this->user_service_client
                ->getDocumentTransactions(
                    $document->id
                );

        $document->transactions =
            $transactions;

        /*
         * Numéro de pièce.
         */
        $document->numero_piece =
            $document->attachments()
                ->whereHas(
                    'attachmentType',
                    function ($query) {
                        $query->where(
                            'slug',
                            'numero-de-piece'
                        );
                    }
                )
                ->value('attachment_number');

        /*
         * Handler spécifique au type de document.
         */
        $handler = app($handlerClass);

        return $handler->enrich(
            $document,
            $base
        );
    }

    /**
 * Enrichissement batch des documents.
 *
 * Les données de base sont indexées par document_id afin
 * de garantir l'alignement entre les documents et leurs bases,
 * indépendamment des clés internes des Collections.
 *
 * @param Collection $documents
 * @param Collection|null $baseDocuments
 *
 * @return Collection
 */
public function enrichBatch(
    Collection $documents,
    Collection $baseDocuments = null
): Collection {
    if ($documents->isEmpty()) {
        return collect();
    }

    // throw new Exception(json_encode($documents), 1);
    

    /*
     * Les bases sont indexées par ID du document.
     *
     * Exemple :
     *
     * [
     *     10 => [...base document 10...],
     *     15 => [...base document 15...],
     *     27 => [...base document 27...],
     * ]
     */
    if ($baseDocuments === null) {
        $baseDocuments = $documents->mapWithKeys(
            function (Document $document) {
                return [
                    (int) $document->id =>
                        $this->getBase($document),
                ];
            }
        );
    } else {
        /*
         * Si les bases ont été fournies par le service,
         * on les réindexe également par document_id.
         *
         * Cela évite toute dépendance à l'index numérique
         * de la Collection.
         */
        $baseDocuments = $baseDocuments->mapWithKeys(
            function ($base, $index) use ($documents) {
                /*
                 * Cas normal : la base contient déjà l'ID.
                 */
                if (
                    is_array($base) &&
                    isset($base['id'])
                ) {
                    return [
                        (int) $base['id'] => $base,
                    ];
                }

                /*
                 * Fallback : récupération du document correspondant
                 * à l'index initial.
                 */
                $document = $documents->get($index);

                if (!$document) {
                    return [];
                }

                return [
                    (int) $document->id => $base,
                ];
            }
        );
    }

    /*
     * Chargement groupé des relations.
     */
    $this->loadBatchRelations(
        $documents
    );


    // throw new Exception(json_encode($documents), 1);

    

    /*
     * Préparation des transactions.
     */
    $transactionsByDocument =
        $this->loadBatchTransactions(
            $documents
        );

    
    // throw new Exception(json_encode($transactionsByDocument), 1);


    

    /*
     * Préparation des numéros de pièce.
     */
    $attachmentsByDocument =
        $this->loadBatchAttachmentNumbers(
            $documents
        );

    // throw new Exception(json_encode($attachmentsByDocument), 1);
    

    /*
     * Enrichissement final.
     */
   return $documents
    ->values()
    ->map(function (Document $document) use (
        $baseDocuments,
        $transactionsByDocument,
        $attachmentsByDocument
    ) {
        $documentId = (int) $document->id;

        $base = $baseDocuments->get($documentId);

        if (!is_array($base)) {
            $base = $this->getBase($document);
        }

        $document->transactions =
            $transactionsByDocument[$documentId] ?? [];

        $document->numero_piece =
            $attachmentsByDocument[$documentId] ?? null;

        $documentType = $document->document_type;

        $relationName =
            optional($documentType)->relation_name;

        if ($relationName) {
            $relationData =
                $document->{$relationName} ?? null;

            $base[$relationName] =
                $relationData
                    ? $relationData->toArray()
                    : null;
        }

        $handlerClass =
            optional($documentType)->enrichment_handler_class;

        if (!$handlerClass) {
            throw new \Exception(
                "Aucun handler d'enrichissement configuré " .
                "pour le type de document '" .
                optional($documentType)->name .
                "'"
            );
        }

        return app($handlerClass)->enrich(
            $document,
            $base
        );
    });
}

    /**
     * Charge les relations des documents en groupe.
     *
     * Laravel effectuera le chargement de la relation
     * pour toute la collection concernée au lieu de faire
     * un load() indépendant pour chaque document.
     *
     * @param Collection $documents
     *
     * @return void
     */
    protected function loadBatchRelations(
        Collection $documents
    ): void {
        /*
         * On regroupe les documents par relation.
         */
        $groups =
            $documents->filter(function (
                Document $document
            ) {
                return !empty(
                    $document
                        ->document_type
                        ->relation_name
                );
            })->groupBy(function (
                Document $document
            ) {
                return $document
                    ->document_type
                    ->relation_name;
            });

        /*
         * Chaque relation est chargée une seule fois
         * pour le groupe de documents concerné.
         */
        foreach ($groups as $relationName => $group) {
            if (!$relationName) {
                continue;
            }

            $group->load(
                $relationName
            );
        }
    }

    /**
     * Prépare les transactions des documents.
     *
     * VERSION TRANSITOIRE.
     *
     * Cette méthode utilise encore l'appel individuel
     * UserServiceClient::getDocumentTransactions().
     *
     * Elle constitue volontairement un point d'isolation :
     * lorsque l'endpoint batch UserService sera disponible,
     * seul ce morceau devra être remplacé.
     *
     * @param Collection $documents
     *
     * @return array
     */
    protected function OldloadBatchTransactions(
        Collection $documents
    ): array {
        $transactions = [];

        foreach ($documents as $document) {
            $documentId =
                (int) $document->id;

            $transactions[$documentId] =
                $this->user_service_client
                    ->getDocumentTransactions(
                        $documentId
                    );
        }

        return $transactions;
    }

    /**
 * Prépare les transactions des documents en une seule requête batch.
 *
 * Les transactions sont indexées par document_id afin de permettre
 * un accès rapide pendant l'enrichissement de l'export.
 *
 * @param Collection $documents
 *
 * @return array<int, array>
 */
protected function loadBatchTransactions(
    Collection $documents
): array {
    $documentIds = $documents
        ->pluck('id')
        ->map(function ($id) {
            return (int) $id;
        })
        ->unique()
        ->values()
        ->all();

    if (empty($documentIds)) {
        return [];
    }

    $transactions =
        $this->user_service_client
            ->getDocumentsTransactions(
                $documentIds
            );

    return $this->indexTransactionsByDocumentId(
        $transactions
    );
}

/**
 * Indexe les transactions par document_id.
 *
 * @param array $transactions
 *
 * @return array<int, array>
 */
protected function indexTransactionsByDocumentId(
    array $transactions
): array {
    $indexed = [];

    foreach ($transactions as $transaction) {
        if (!isset($transaction['document_id'])) {
            continue;
        }

        $documentId =
            (int) $transaction['document_id'];

        $indexed[$documentId][] =
            $transaction;
    }

    return $indexed;
}

    /**
     * Récupère les numéros de pièce pour plusieurs documents.
     *
     * Cette méthode utilise une requête SQL groupée au lieu
     * de faire une requête attachments() par document.
     *
     * @param Collection $documents
     *
     * @return array
     */
    protected function loadBatchAttachmentNumbers(
        Collection $documents
    ): array {
        $documentIds =
            $documents
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values();

        if ($documentIds->isEmpty()) {
            return [];
        }

        /*
         * On récupère les attachments concernés en une seule
         * requête.
         *
         * Le nom exact de la table dépend de ton modèle
         * Attachment. Ici nous passons par la relation du
         * premier document afin de conserver la structure
         * existante du projet.
         */
        $firstDocument =
            $documents->first();

        if (!$firstDocument) {
            return [];
        }

        $attachments =
            $firstDocument
                ->attachments()
                ->whereIn(
                    'document_id',
                    $documentIds->all()
                )
                ->whereHas(
                    'attachmentType',
                    function ($query) {
                        $query->where(
                            'slug',
                            'numero-de-piece'
                        );
                    }
                )
                ->get([
                    'document_id',
                    'attachment_number',
                ]);

        return $attachments
            ->groupBy('document_id')
            ->map(function ($items) {
                $item =
                    $items->first();

                return $item
                    ? $item->attachment_number
                    : null;
            })
            ->toArray();
    }
}

// ### Important

// Il y a volontairement une étape intermédiaire dans cette version :

// ```php
// loadBatchTransactions()
// ```

// fait encore :

// ```php
// foreach ($documents as $document) {
//     getDocumentTransactions($document->id);
// }


// Donc **les appels HTTP ne sont pas encore supprimés**.

// En revanche, nous avons déjà préparé l'architecture pour pouvoir remplacer uniquement :

// ```php
// protected function loadBatchTransactions(...)
// ```

// par :

// ```php
// protected function loadBatchTransactions(...)
// {
//     $documentIds = $documents
//         ->pluck('id')
//         ->map(function ($id) {
//             return (int) $id;
//         })
//         ->unique()
//         ->values()
//         ->all();

//     return $this->user_service_client
//         ->getDocumentTransactionsBatch(
//             $documentIds
//         );
// }
// et passer ainsi de :

// ```text
// 100 documents
// → 100 appels UserService
// ```

// à :

// ```text
// 100 documents
// → 1 appel UserService
// ```

// **C'est cette prochaine modification de `UserServiceClient` qui va apporter le vrai gain de performance côté HTTP.**