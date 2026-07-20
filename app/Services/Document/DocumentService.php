<?php

namespace App\Services\Document;

use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;

class DocumentService

{
     private DocumentEnrichmentManager $documentEnrichmentManager;

   

    public function __construct(
        DocumentEnrichmentManager $documentEnrichmentManager
    ) 
    {
        $this->documentEnrichmentManager = $documentEnrichmentManager;
    }
        public function enrichDocument(Document $document) {

     $document->load("document_type");

        $totalPaid = $document->payments()->sum("amount");

        $document->paid_amount = $totalPaid;

        $document->formatted_amount = $document->amount
            ? number_format($document->amount, 0, ",", ".")
            : null;

        $document->load(
            "document_type",
            "attachments.file",
            "secondary_attachments"
        );

        $document = $this->documentEnrichmentManager->enrich($document);

    

        return $document;

    }
}