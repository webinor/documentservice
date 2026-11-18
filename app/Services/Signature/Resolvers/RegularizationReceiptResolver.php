<?php

namespace App\Services\Signature\Resolvers;


use App\Contracts\Signature\ReceiptSourceResolverInterface;
use App\Models\Misc\Document;
use Illuminate\Support\Collection;

class RegularizationReceiptResolver implements ReceiptSourceResolverInterface
{
    public function supports(Document $document): bool
    {
        return $document->regularization_sheet()->exists();
    }

    public function resolve(Document $document): Collection
    {
        $sheet = $document->regularization_sheet()
            ->with('receipts.file')
            ->first();

        if (!$sheet) {
            return collect();
        }

        return $sheet->receipts->map(function ($receipt) {
            return [
                'source' => $receipt,
                'file' => $receipt->file,
            ];
        });
    }
}