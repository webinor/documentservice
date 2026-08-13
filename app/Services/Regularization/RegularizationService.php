<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegularizationService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {
        $sheet = $document->regularization_sheet()->create([
            "reason" => $validated["titre"] ?? null,

            "regularization_type" =>
                $validated["regularization_type"] ?? "INTERNAL",

            "assistance_mode" =>
                $validated["assistance_mode"] ?? null,

            "case_number" =>
                $validated["case_number"] ?? null,
        ]);

        foreach ($validated["libelles"] ?? [] as $index => $item) {
            $sheet->items()->create([
                "designation" =>
                    isset($item["libelle"])
                        ? Str::upper($item["libelle"])
                        : null,

                "planned_quantity" =>
                    $item["planned_quantity"]
                    ?? $item["quantite"]
                    ?? 1,

                "planned_amount" =>
                    $item["montant"] ?? 0,

                "actual_amount" => null,

                "comment" =>
                    $item["comment"] ?? null,

                "sort_order" => $index + 1,
            ]);
        }
    }

    public function update(
        Document $document,
        array $validated
    ): void {
        DB::transaction(function () use ($document, $validated) {

            $sheet = $document->regularization_sheet;

            if (!$sheet) {
                // Si aucune fiche n'existe encore, on la crée.
                $this->create($document, $validated);

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Mise à jour de la fiche
            |--------------------------------------------------------------------------
            */

            $sheet->update([
                "reason" =>
                    $validated["titre"] ?? $sheet->reason,

                "regularization_type" =>
                    $validated["regularization_type"]
                    ?? $sheet->regularization_type,

                "assistance_mode" =>
                    $validated["assistance_mode"]
                    ?? $sheet->assistance_mode,

                "case_number" =>
                    $validated["case_number"]
                    ?? $sheet->case_number,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Mise à jour des lignes
            |--------------------------------------------------------------------------
            */

            $sheet->items()->delete();

            foreach ($validated["libelles"] ?? [] as $index => $item) {

                $sheet->items()->create([
                    "designation" =>
                        isset($item["libelle"])
                            ? Str::upper($item["libelle"])
                            : null,

                    "planned_quantity" =>
                        $item["planned_quantity"]
                        ?? $item["quantite"]
                        ?? 1,

                    "planned_amount" =>
                        $item["montant"] ?? 0,

                    "actual_amount" => null,

                    "comment" =>
                        $item["comment"] ?? null,

                    "sort_order" => $index + 1,
                ]);
            }
        });
    }

    public function markAsPaid(array $payload)
    {
        // ...
    }
}