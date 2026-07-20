<?php

namespace App\Services\InvoiceProvider;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;

class InvoiceProviderDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document,  array $base): array
    {
        $document->load([
            'invoice_provider',
            'attachments',
        ]);

        
        return $document->toArray();
    }
}