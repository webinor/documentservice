<?php

namespace App\Services\Purchase;

use App\Models\Misc\Document;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class PurchaseRequestService implements DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $validated
    ): void {

        $purchaseRequest = PurchaseRequest::create([
            'document_id' => $document->id,

            'description' => $validated['description'] ?? null,

            'destination_service_id' =>
                $validated['destination_service_id'] ?? null,

            // 'priority' => $validated['priority'] ?? 'MEDIUM',

            // 'is_it_equipment' =>  $validated['is_it_equipment'] ?? false,

             "requested_by" => request()->get("user")["id"],
        ]);

        foreach (($validated['items'] ?? []) as $item) {

            PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,

                'designation' =>
                    $item['designation'] ?? null,

                'requested_quantity' =>
                    $item['quantity'] ?? 1,

                'specification' =>
                    $item['specification'] ?? null,
            ]);
        }
    }
}