<?php

namespace App\Services\Signature\Resolvers;


use App\Contracts\Signature\ReceiptSourceResolverInterface;
use App\Models\Misc\Document;
use App\Models\PurchaseReceipt;
use Illuminate\Support\Collection;

class PurchaseReceiptResolver implements ReceiptSourceResolverInterface
{
    public function supports(Document $document): bool
    {
        return $document->purchase_request()->exists();
    }

    public function resolve(Document $document): Collection
    {
        $purchaseRequest = $document->purchase_request()
            ->with('request_order')
            ->first();

        if (
            !$purchaseRequest ||
            !$purchaseRequest->request_order
        ) {
            return collect();
        }

        $requestOrderId = $purchaseRequest->request_order->id;

        $receipts = PurchaseReceipt::query()
            ->where('request_order_id', $requestOrderId)
            ->with('file')
            ->get();

        return $receipts->map(function ($receipt) {
            return [
                'source' => $receipt,
                'file' => $receipt->file,
            ];
        });
    }
}
