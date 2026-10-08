<?php

namespace App\Services\Purchase;


use App\Models\Misc\Document;
use App\Models\PurchaseRequestItem;
use App\Services\DocumentType\DocumentEnrichmentHandlerInterface;
use App\Services\UserServiceClient;
use Exception;
use Illuminate\Support\Facades\Http;

class PurchaseDocumentEnrichmentHandler implements DocumentEnrichmentHandlerInterface
{
    public function enrich(Document $document, array $base): array
{


    // throw new Exception(json_encode($document->purchase_request->id), 1);


    $purchase_request_items = PurchaseRequestItem::wherePurchaseRequestId($document->purchase_request->id)->get();

    $document->purchase_request->purchase_request_items = $purchase_request_items;
    // $userClient = new UserServiceClient();

    // $actor_details = $userClient->resolveActor(
    //     $document->actor_type,
    //     $document->actor_id
    // );

    // $document->actor_details = $actor_details;

    

    

    return $document->toArray();
}


}