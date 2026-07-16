<?php

namespace App\Services\Regularization;



use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class RegularizationService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {
        
     $data = [
            "reason" => $validated["titre"] ?? null,
            "amount" => $validated["montant"] ?? null,
        ];

         $document->regularization_sheet()->create($data);
    }

     public function markAsPaid(array $payload)
{
    // $regulation = TaxiRegulation::where('transaction_code', $payload['transaction_code'])->firstOrFail();

    // $regulation->update([
    //     'status' => 'paid',
    //     'paid_at' => now(),
    // ]);
}
}