<?php

namespace App\Services\FeeNote;

use App\Models\Misc\Document;

class FeeNoteService
{
    public function create(
        Document $document,
        array $validated
    ): void {
        $data = [
            "reason" => $validated["titre"] ?? null,
            "amount" => $validated["montant"] ?? null,
        ];

        $document->fee_note()->create($data);
    }

    public function update(
        Document $document,
        array $validated
    ): void {
        $data = [
            "reason" => $validated["titre"] ?? null,
            "amount" => $validated["montant"] ?? null,
        ];

        $feeNote = $document->fee_note;

        if (!$feeNote) {
            $document->fee_note()->create($data);
            return;
        }

        $feeNote->update($data);
    }

    public function markAsPaid(array $payload)
    {
    }
}