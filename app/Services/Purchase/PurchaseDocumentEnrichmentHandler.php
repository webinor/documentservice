<?php

namespace App\Services\Purchase;


use App\Models\Misc\Document;
use App\Models\PurchaseRequestItem;
use App\Services\DepartmentServiceClient;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Facades\Http;

class PurchaseDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{

 protected DepartmentServiceClient $departmentClient;

    public function __construct(
        DepartmentServiceClient $departmentClient
    ) {
        $this->departmentClient = $departmentClient;
    }


   

public function enrich(Document $document, array $base): array
{
    $purchaseRequest = $document->purchase_request;

    if ($purchaseRequest) {

        // Charger les articles de la demande d'achat
        $purchaseRequest->purchase_request_items =
            PurchaseRequestItem::wherePurchaseRequestId($purchaseRequest->id)->get();

        // Charger le bon de commande associé
        $purchaseRequest->load([
    'request_order.items',
    'request_order.supplierQuote',
]);
    }

    $actor_details = $this->departmentClient->resolveDepartment(
        $document->actor_id
    );

    $document->destination_service_details = $actor_details;

    return $document->toArray();
}


}