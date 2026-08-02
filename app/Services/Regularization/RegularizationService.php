<?php

namespace App\Services\Regularization;



use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Str;

class RegularizationService implements DocumentTypeHandlerInterface
{
    public function create(
    Document $document,
    array $validated
): void {

    $sheet = $document->regularization_sheet()->create([
        "reason"             => $validated["titre"] ?? null,
        // "amount"             => collect($validated['libelles'])->sum('montant'),
        "regularization_type"=> $validated["regularization_type"] ?? "INTERNAL",
        "assistance_mode"    => $validated["assistance_mode"] ?? null,
        "case_number"        => $validated["case_number"] ?? null,
    ]);


    foreach ($validated["libelles"] ?? [] as $index => $item) {

        $sheet->items()->create([
            "designation"    => Str::upper($item["libelle"]) ?? null,
            "planned_quantity"       => $item["planned_quantity"] ?? 1,
            // "unit_price"     => $item["unit_price"] ?? 0,

            "planned_amount" => $item["montant"],// ?? (($item["quantity"] ?? 1) * ($item["unit_price"] ?? 0)),

            "actual_amount"  => null,

            "old_receipt"    => $item["old_receipt"] ?? null,
            "comment"        => $item["comment"] ?? null,
            "sort_order"     => $index + 1,
        ]);
    }
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