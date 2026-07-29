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

            "regularization_type" => $validated["regularization_type"] ?? "INTERNAL",
            "assistance_mode" => $validated["assistance_mode"] ?? null,
            "case_number" => $validated["case_number"] ?? null,

        ];

        // throw new \Exception(json_encode($data), 1);
        

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