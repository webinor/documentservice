<?php

namespace App\Services\TaxiPaper;


use App\Models\Misc\Document;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class TaxiPaperService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {
        
      $data = [
            "reason" => $validated["motif"] ?? null,
            "rides" => $validated["trajets"] ?? null,
            "beneficiary" => $validated["beneficiaire"] ?? null,
        ];

         $document->taxi_paper()->create($data);
    }
}