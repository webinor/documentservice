<?php

namespace App\Services\DocumentType;

use App\Models\Misc\Document;

interface DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array;
}