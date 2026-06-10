<?php

namespace App\Services\Purchase;


use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;
use App\Services\Purchase\PurchaseRequestService;

class PurchaseRequestDocumentHandler
implements DocumentTypeHandlerInterface
{
    protected $purchaseRequestService;

    public function __construct(
        PurchaseRequestService $purchaseRequestService
    ){
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function create(
        Document $document,
        array $data
    ): void
    {
        $this->purchaseRequestService->create(
            $document,
            $data
        );
    }
}