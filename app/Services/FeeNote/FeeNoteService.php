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
    // $regulation = FeeNoteRegulation::where('transaction_code', $payload['transaction_code'])->firstOrFail();

    // $regulation->update([
    //     'status' => 'paid',
    //     'paid_at' => now(),
    // ]);
}
}