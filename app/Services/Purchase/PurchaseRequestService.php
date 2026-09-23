<?php

namespace App\Services\Purchase;

use App\Models\Misc\Document;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;

class PurchaseRequestService
{
    public function create(
        Document $document,
        array $validated
    ): void {
        $purchaseRequest = PurchaseRequest::create([
            'document_id' => $document->id,

            'description' =>
                $validated['description'] ?? null,

            'destination_service_id' =>
                $validated['destination_service_id'] ?? null,

            'category' =>
                $validated['category'] ?? null,

            'priority' =>
                $validated['priority'] ?? 'MEDIUM',

            'requested_by' =>
                request()->get('user')['id'],
        ]);

        foreach (($validated['libelles'] ?? []) as $item) {

            PurchaseRequestItem::create([
                'purchase_request_id' =>
                    $purchaseRequest->id,

                'designation' =>
                    $item['libelle'] ?? null,

                'requested_quantity' =>
                    $item['quantite'] ?? 1,

                'specification' =>
                    $item['specification'] ?? null,
            ]);
        }
    }

    public function update(
        Document $document,
        array $validated
    ): void {
        $purchaseRequest = PurchaseRequest::where(
            'document_id',
            $document->id
        )->firstOrFail();

        $purchaseRequest->update([
            'description' =>
                $validated['description'] ?? null,

            'destination_service_id' =>
                $validated['destination_service_id'] ?? null,

            'category' =>
                $validated['category'] ?? null,

            'priority' =>
                $validated['priority'] ?? 'MEDIUM',
        ]);

        /*
         * On supprime les anciennes lignes
         * puis on recrée les lignes correspondant
         * aux données actuellement envoyées.
         */
        PurchaseRequestItem::where(
            'purchase_request_id',
            $purchaseRequest->id
        )->delete();

        foreach (($validated['libelles'] ?? []) as $item) {

            PurchaseRequestItem::create([
                'purchase_request_id' =>
                    $purchaseRequest->id,

                'designation' =>
                    $item['libelle'] ?? null,

                'requested_quantity' =>
                    $item['quantite'] ?? 1,

                'specification' =>
                    $item['specification'] ?? null,
            ]);
        }
    }
}