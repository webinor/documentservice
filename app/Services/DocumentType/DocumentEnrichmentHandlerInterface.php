<?php

namespace App\Services\DocumentType;

use App\Models\Misc\Document;
use Illuminate\Support\Collection;

interface DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array;

    //     public function enrichBatch(
    //     Collection $documents,
    //     array $bases = []
    // ): Collection;
}