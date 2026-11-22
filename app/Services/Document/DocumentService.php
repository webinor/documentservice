<?php

namespace App\Services\Document;

use App\Managers\DocumentEnrichmentManager;
use App\Models\Misc\Document;
use Illuminate\Support\Str;

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
            "secondary_attachments",
            "document_references"
        );

          // Ajout des liens de consultation et de téléchargement
    // des pièces jointes via l'API Gateway.
    $this->enrichAttachments($document);

        $document = $this->documentEnrichmentManager->enrich($document);

        

    
            //  throw new \Exception(json_encode($document));


        return $document;

    }


    /**
 * Enrichit les pièces jointes avec leurs liens de visualisation
 * et de téléchargement accessibles depuis l'API Gateway.
 */
private function enrichAttachments(Document $document): void
{
    // $baseUrl = rtrim(config('services.app_url'), '/');
    $baseUrl =  config('services.app_url');// rtrim(config('app.url'), '/');


    if (!$baseUrl) {
        return;
    }

    foreach ($document->attachments as $attachment) {

        $file = $attachment->file;

        if (!$file || !$file->path) {
            continue;
        }

        $path = rawurlencode($file->path);

        $attachment->setAttribute(
            'view_url',
            "{$baseUrl}/api/documents/document-attachments/{$path}/view"
        );

        $attachment->setAttribute(
            'download_url',
            "{$baseUrl}/api/documents/document-attachments/{$path}/download"
        );
    }
}

     public function getDoc($identifier) : Document {


//      throw new \Exception(json_encode([
//     'identifier' => $identifier,
//     'is_uuid' => Str::isUuid($identifier),
// ]));

     
    if (Str::isUuid($identifier)) {
    $document = Document::with(['document_type'])->where('uuid', $identifier)->firstOrFail();

//     throw new \Exception(json_encode([
//     'identifier' => $identifier,
//     'document' => $document,
// ]));

    // throw new \Exception(json_encode('$document'), 1);


} else {

    
    $document = Document::with(['document_type'])->findOrFail($identifier);
    
}

    


return $document;
        
    }
}