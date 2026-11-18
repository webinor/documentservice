<?php

namespace App\Services\Signature\Resolvers;

use App\Contracts\Signature\ReceiptSourceResolverInterface;
use App\Models\Misc\Document;
use Illuminate\Support\Collection;

class MissionReceiptResolver implements ReceiptSourceResolverInterface
{
    public function supports(Document $document): bool
    {
        return $document->mission()->exists();
    }

    public function resolve(Document $document): Collection
    {
        $mission = $document->mission()
            ->with('mission_expenses.file')
            ->first();

        if (!$mission) {
            return collect();
        }

        return $mission->mission_expenses
            ->map(function ($expense) {
                return [
                    'source' => $expense,
                    'file' => $expense->file,
                ];
            });
    }
}