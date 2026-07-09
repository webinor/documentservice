<?php

namespace App\Services\FeeNote;



use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class FeeNoteService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {
        
      $data = [
            "reason" => $validated["titre"] ?? null,
            "amount" => $validated["montant"] ?? null,
            // "beneficiary" => $validated["beneficiaire"] ?? null,
        ];

         $document->fee_note()->create($data);
    }

     public function markAsPaid(array $payload)
{
   
}
}