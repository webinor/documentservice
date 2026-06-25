<?php

namespace App\Managers;

use App\Models\Misc\Document;

class DocumentEnrichmentManager
{
    public function enrich(Document $document, array $base): array
    {
        $type = $document->document_type;

        $handlerClass = $type->enrichment_handler_class;

        if (!$handlerClass) {
    throw new \Exception(
        "Aucun handler d'enrichissement configuré pour le type de document '{$type->name}'"
    );
}

        if (!$handlerClass) {
            // return $base; // fallback ancien système
        }

        $handler = app($handlerClass);

        return $handler->enrich($document, $base);
    }
}