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



    // throw new Exception(json_encode($document->actor_id), 1);


    $purchase_request_items = PurchaseRequestItem::wherePurchaseRequestId($document->purchase_request->id)->get();

    $document->purchase_request->purchase_request_items = $purchase_request_items;

    // $this->departmentClient->getDepartmentById($departmentId);
    // $userClient = new UserServiceClient();



    $actor_details = $this->departmentClient->resolveDepartment(
        // $document->actor_type,
        $document->actor_id
    );

    // throw new Exception(json_encode($actor_details), 1);


    $document->destination_service_details = $actor_details;

    

    

    return $document->toArray();
}


}