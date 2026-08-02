<?php

namespace App\Services\TaxiPaper;


use App\Models\Misc\Document;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\TaxiRegulation;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Str;

class TaxiPaperService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {
        
      $data = [
            "reason" => Str::upper($validated["titre"]) ?? null,
            "rides" => $validated["libelles"] ?? null,
            // "beneficiary" => $validated["beneficiaire"] ?? null,
        ];

         $document->taxi_paper()->create($data);
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