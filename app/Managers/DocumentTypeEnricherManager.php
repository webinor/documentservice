<?php

namespace App\Managers;

use App\Models\Misc\Document;
use App\Services\Regularization\RegularizationDocumentTypeEnrichmentHandler;
use App\Services\UserServiceClient;
use Illuminate\Support\Collection;

class DocumentTypeEnricherManager
{
    /**
     * Enrichit plusieurs documents.
     *
     * @param Collection $documents
     * @return Collection
     */
    public function enrich(
        Collection $documents
    ): Collection {
        return $documents
            ->map(function (Document $document) {
                return $this->enrichDocument($document);
            })
            ->values();
    }

    /**
     * Enrichit un document.
     *
     * @param Document $document
     * @return array
     */
    public function enrichDocument(
        Document $document
    ): array {
        $documentType =
            $document->document_type;

        $data = [
            "id" =>
                $document->id,

            "created_by" =>
                $document->created_by,

            "actor_type" =>
                $document->actor_type,

            "actor_id" =>
                $document->actor_id,

            "document_type" =>
                $documentType,

            "document_type_id" =>
                $document->document_type_id,

            "document_type_relation_name" =>
                $documentType
                    ? $documentType->relation_name
                    : null,

            "document_type_version" =>
                $this->resolveVersion(
                    $document,
                    $documentType
                ),
        ];

        return $this->applyEnricher(
            $document,
            $data
        );
    }

    /**
     * Applique l'enrichisseur correspondant
     * au type de document.
     *
     * @param Document $document
     * @param array $data
     * @return array
     */
    protected function applyEnricher(
        Document $document,
        array $data
    ): array {


    $userClient = new UserServiceClient();

       $document->actor_details =
            $userClient->resolveActor(
                $document->actor_type,
                $document->actor_id
            );


        $documentType =
            $document->document_type;

        if (!$documentType) {
            return $data;
        }

        $handlerClass =
            $this->resolveEnricherHandler(
                $documentType->slug
            );

        if (!$handlerClass) {
           
        // throw new \Exception("Enricher $documentType->slug introuvable", 1);
            
            return $data;
        }

        $handler =
            app($handlerClass);

        return $handler->enrich(
            $document,
            $data
        );
    }

    /**
     * Détermine l'enrichisseur à utiliser
     * en fonction du nom du type de document.
     *
     * @param string|null $documentTypeSlug
     * @return string|null
     */
    protected function resolveEnricherHandler(
        ?string $documentTypeSlug
    ): ?string {
        switch ($documentTypeSlug) {
            case "papier-taxi":

                return null;

                // return \App\Services\TaxiPaper\TaxiPaperDocumentEnrichmentHandler::class;

            case "note-de-frais":

                return null;

                // return \App\Services\FeeNote\FeeNoteDocumentEnrichmentHandler::class;

            case "fiche-a-regulariser":

                return RegularizationDocumentTypeEnrichmentHandler::class;

            case "demande-d-absence":

                return null;

                // return \App\Services\Absence\AbsenceDocumentEnrichmentHandler::class;

            default:

                return null;
        }
    }

    /**
     * Résout la version du type de document.
     *
     * @param Document $document
     * @param mixed $documentType
     * @return int
     */
    protected function resolveVersion(
        Document $document,
        $documentType
    ): int {
        return 1;
    }
}