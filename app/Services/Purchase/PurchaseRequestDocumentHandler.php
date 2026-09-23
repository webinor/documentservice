<?php

namespace App\Services\Purchase;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class PurchaseRequestDocumentHandler
    implements DocumentTypeHandlerInterface
{
    protected PurchaseRequestService $purchaseRequestService;

    public function __construct(
        PurchaseRequestService $purchaseRequestService
    ) {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function create(
        Document $document,
        array $data
    ): void {
        $this->purchaseRequestService->create(
            $document,
            $data
        );
    }

    public function update(
        Document $document,
        array $data
    ): void {
        $this->purchaseRequestService->update(
            $document,
            $data
        );
    }
}