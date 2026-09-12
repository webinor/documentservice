<?php

namespace App\Services\Export;

use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentExportService
{
    /**
     * Gestionnaire d'enrichissement des documents.
     *
     * @var DocumentEnrichmentManager
     */
    protected DocumentEnrichmentManager $documentEnrichmentManager;

    /**
     * Resolver des colonnes d'export.
     *
     * @var DocumentExportColumnResolver
     */
    protected DocumentExportColumnResolver $columnResolver;

    /**
     * Constructeur.
     *
     * @param DocumentEnrichmentManager $documentEnrichmentManager
     * @param DocumentExportColumnResolver $columnResolver
     */
    public function __construct(
        DocumentEnrichmentManager $documentEnrichmentManager,
        DocumentExportColumnResolver $columnResolver
    ) {
        $this->documentEnrichmentManager =
            $documentEnrichmentManager;

        $this->columnResolver =
            $columnResolver;
    }

    /**
     * Exporte les documents fournis par le workflow-service.
     *
     * IMPORTANT :
     *
     * - Les IDs reçus sont considérés comme autorisés.
     * - Aucun filtre workflow n'est réappliqué ici.
     * - Le document-service récupère uniquement les documents
     *   correspondant aux IDs reçus.
     * - Les colonnes reçues du frontend sont résolues via
     *   DocumentExportColumnResolver.
     * - Le workflow-service reste la source de vérité pour
     *   les métadonnées workflow.
     *
     * @param array $documentIds
     * @param string $documentType
     * @param array $columns
     * @param array $workflowMetadata
     * @param bool $shouldEnrich
     *
     * @return BinaryFileResponse
     */
    public function export(
        array $documentIds,
        string $documentType,
        array $columns,
        array $workflowMetadata = [],
        bool $shouldEnrich = true
    ): BinaryFileResponse {
        /*
         * ------------------------------------------------------------------
         * 1. Résolution des colonnes
         * ------------------------------------------------------------------
         *
         * Le frontend envoie uniquement des clés :
         *
         * [
         *     "title",
         *     "actor_full_name",
         *     "dynamic_amount",
         *     "created_at",
         *     "workflow_status"
         * ]
         *
         * Le resolver transforme ces clés en définitions complètes :
         *
         * [
         *     [
         *         "key" => "title",
         *         "label" => "Titre",
         *         "type" => "text",
         *         "value" => Closure
         *     ],
         *     ...
         * ]
         *
         * C'est cette structure que DocumentsExport attend.
         */
        $resolvedColumns = $this->columnResolver->resolve(
            $columns
        );

        /*
         * ------------------------------------------------------------------
         * 2. Récupération des documents
         * ------------------------------------------------------------------
         *
         * Les IDs ont déjà été déterminés et autorisés par
         * workflow-service.
         *
         * Aucun filtre workflow n'est appliqué ici.
         */
        $documents = $this->getDocuments(
            $documentIds,
            $documentType,
            $shouldEnrich
        );

        /*
         * ------------------------------------------------------------------
         * 3. Ajout des métadonnées workflow
         * ------------------------------------------------------------------
         *
         * workflow_status appartient au workflow-service.
         *
         * Le document-service ne tente donc pas de recalculer
         * cet état.
         */
        $documents = $this->attachWorkflowMetadata(
            $documents,
            $workflowMetadata
        );

        /*
         * ------------------------------------------------------------------
         * 4. Transformation des documents en lignes Excel
         * ------------------------------------------------------------------
         *
         * DocumentsExport reçoit maintenant les définitions de colonnes
         * complètes.
         *
         * C'est important car DocumentsExport utilise notamment :
         *
         * - label
         * - type
         * - value
         */
        $rows = $this->buildRows(
            $documents,
            $resolvedColumns,
            $workflowMetadata
        );

        /*
         * ------------------------------------------------------------------
         * 5. Nom du fichier
         * ------------------------------------------------------------------
         */
        $fileName = $this->buildFileName(
            $documentType
        );

        /*
         * ------------------------------------------------------------------
         * 6. Génération du fichier Excel
         * ------------------------------------------------------------------
         */
        return Excel::download(
            new DocumentsExport(
                $rows,
                $resolvedColumns,
                $documentType
            ),
            $fileName
        );
    }

    /**
     * Récupère les documents correspondant exactement aux IDs reçus.
     *
     * Aucun filtre issu de la requête frontend n'est appliqué ici.
     *
     * @param array $documentIds
     * @param string $documentType
     * @param bool $shouldEnrich
     *
     * @return Collection
     */
    protected function getDocuments(
        array $documentIds,
        string $documentType,
        bool $shouldEnrich = true
    ): Collection {
        if (empty($documentIds)) {
            return collect();
        }

        /*
         * ------------------------------------------------------------------
         * Normalisation des IDs
         * ------------------------------------------------------------------
         */
        $documentIds = collect($documentIds)
            ->filter(function ($id) {
                return is_numeric($id);
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        if (empty($documentIds)) {
            return collect();
        }

        /*
         * ------------------------------------------------------------------
         * Relation du type de document
         * ------------------------------------------------------------------
         */
        $documentTypes = [
            $documentType,
        ];

        /*
         * ------------------------------------------------------------------
         * Requête documentaire
         * ------------------------------------------------------------------
         *
         * IMPORTANT :
         *
         * On ne réutilise volontairement pas getFilteredDocuments().
         *
         * Le workflow-service a déjà appliqué ses propres filtres,
         * permissions et règles de visibilité.
         */
        $query = Document::query();

        $query->whereIn(
            "id",
            $documentIds
        );

        /*
         * Vérification du type de document.
         */
        if (!empty($documentType)) {
            $query->whereHas(
                $documentType
            );
        }

        /*
         * Chargement des relations nécessaires à l'enrichissement.
         */
        $query->with(
            array_merge(
                [
                    "document_type",
                ],
                $documentTypes
            )
        );

        /*
         * Ordre déterministe.
         */
        $documents = $query
            ->orderBy("id")
            ->get();

        /*
         * Enrichissement via le mécanisme existant.
         */
        return $this->enrichDocuments(
            $documents,
            $documentTypes,
            $shouldEnrich
        );
    }

    /**
     * Enrichit les documents avec le mécanisme existant du
     * document-service.
     *
     * @param Collection $documents
     * @param array $documentTypes
     * @param bool $shouldEnrich
     *
     * @return Collection
     */
    protected function enrichDocuments(
        Collection $documents,
        array $documentTypes,
        bool $shouldEnrich = true
    ): Collection {
        return $documents->map(function ($doc) use (
            $documentTypes,
            $shouldEnrich
        ) {
            $type = $doc->document_type;

            $handlerClass =
                $type->enrichment_handler_class;

            /*
             * Structure de base identique à celle de
             * getFilteredDocuments().
             */
            $base = [
                "id" => $doc->id,
                "code" => $doc->code,
                "amount" => $doc->dynamic_amount,
                "dynamic_amount" => $doc->dynamic_amount,
                "title" => $doc->title,
                "date_due" => $doc->date_due,
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
            ];

            /*
             * Si l'enrichissement est désactivé,
             * on retourne les données de base.
             */
            if (!$shouldEnrich) {
                return $base;
            }

            /*
             * Même comportement que getFilteredDocuments().
             */
            if (!$handlerClass) {
                throw new \Exception(
                    "Aucun enricher pour {$type}"
                );
            }

            return $this->documentEnrichmentManager->enrich(
                $doc,
                $base
            );
        });
    }

    /**
     * Ajoute les métadonnées workflow aux documents.
     *
     * @param Collection $documents
     * @param array $workflowMetadata
     *
     * @return Collection
     */
    protected function attachWorkflowMetadata(
        Collection $documents,
        array $workflowMetadata
    ): Collection {
        return $documents->map(function ($document) use (
            $workflowMetadata
        ) {
            $documentId = (string) data_get(
                $document,
                "id"
            );

            $metadata =
                $workflowMetadata[$documentId]
                ?? $workflowMetadata[(int) $documentId]
                ?? [];

            foreach ($metadata as $key => $value) {
                $document[$key] = $value;
            }

            return $document;
        });
    }

    /**
     * Construit les lignes qui seront réellement écrites dans Excel.
     *
     * Chaque ligne contient les valeurs dans le même ordre que
     * les colonnes résolues.
     *
     * @param Collection $documents
     * @param array $columns
     * @param array $workflowMetadata
     *
     * @return array
     */
    protected function buildRows(
        Collection $documents,
        array $columns,
        array $workflowMetadata
    ): array {
        $rows = [];

        foreach ($documents as $document) {
            $row = [];

            /*
             * Récupération des métadonnées workflow propres
             * au document courant.
             */
            $documentId = (string) data_get(
                $document,
                "id"
            );

            $documentWorkflowMetadata =
                $workflowMetadata[$documentId]
                ?? $workflowMetadata[(int) $documentId]
                ?? [];

            foreach ($columns as $column) {
                /*
                 * Chaque colonne résolue contient son propre resolver
                 * de valeur.
                 */
                $valueResolver = $column["value"];

                $row[] = $valueResolver(
                    $document,
                    $documentWorkflowMetadata
                );
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Construit le nom du fichier Excel.
     *
     * @param string $documentType
     *
     * @return string
     */
    protected function buildFileName(
        string $documentType
    ): string {
        $documentType = trim(
            $documentType
        );

        if ($documentType === "") {
            $documentType = "documents";
        }

        /*
         * Nettoyage du nom du type de document.
         */
        $documentType = preg_replace(
            "/[^A-Za-z0-9_-]+/",
            "-",
            $documentType
        );

        $documentType = trim(
            $documentType,
            "-"
        );

        if ($documentType === "") {
            $documentType = "documents";
        }

        return strtolower(
            $documentType
            . "-export-"
            . now()->format("Ymd_His")
            . ".xlsx"
        );
    }
}