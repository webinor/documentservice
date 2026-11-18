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
protected UserServiceClient $userServiceClient;

public function __construct(
    DepartmentServiceClient $departmentClient,
    UserServiceClient $userServiceClient
) {
    $this->departmentClient = $departmentClient;
    $this->userServiceClient = $userServiceClient;
}


   

public function enrich(Document $document, array $base): array
{
    $purchaseRequest = $document->purchase_request;

    if ($purchaseRequest) {

        // Charger les articles de la demande d'achat.
        $purchaseRequest->purchase_request_items =
            PurchaseRequestItem::wherePurchaseRequestId(
                $purchaseRequest->id
            )->get();

        // Charger le bon de commande et ses relations.
        $purchaseRequest->load([
            'request_order.items',
            'request_order.supplierQuote',
        ]);

        /*
         * Enrichir le bon de commande avec
         * l'utilisateur associé à l'agent de paiement.
         */
        $requestOrder = $purchaseRequest->request_order;

        if ($requestOrder) {

            $assignedAgentId =
                $requestOrder->payment_agent_employee_id;

            $assignedAgentUser = null;
            $assignedAgent = null;

            if ($assignedAgentId) {

                $assignedAgent = $this->userServiceClient
                    ->resolveActor(
                        'EMPLOYEE',
                        (int) $assignedAgentId
                    );

                $assignedAgentUser = data_get(
                    $assignedAgent,
                    'user'
                );
            }

            $requestOrder->payment_agent =
                $assignedAgent;
        }
    }

    // Enrichir le service destinataire.
    $actorDetails = $this->departmentClient->resolveDepartment(
        $document->actor_id
    );

    $document->destination_service_details = $actorDetails;

    return $document->toArray();
}


public function Oldenrich(Document $document, array $base): array
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