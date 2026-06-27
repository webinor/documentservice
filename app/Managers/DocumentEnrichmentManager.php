<?php

namespace App\Managers;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;

class DocumentEnrichmentManager
{

protected UserServiceClient $user_service_client;

 private $documents_relation = [
        "facture-fournisseur-medical" => "invoice_provider.ledger_code",
        "facture-fournisseur-informatique" => "invoice_provider",
        "facture-note-honoraire" => "invoice_provider",
        "papier-taxi" => "taxi_paper",
        "note-de-frais" => "fee_note",
        "demande-d-absence" => "absence_request",
        "mission" => "mission.mission_expenses.expense_category",
        "demande-achat" => "purchase_request.purchase_request_items",

        "purchase-settlement" =>
            "purchase_settlement.purchase_settlement_items",
    ];

    
          public function __construct(
        UserServiceClient $user_service_client
    ) {
        $this->user_service_client = $user_service_client;
    }

    public function getBase(Document $doc) : array {

    return 

         $base = [
                "id" => $doc->id,
                "code" => $doc->code,
                "amount" => $doc->dynamic_amount,
                "title" => $doc->title,
                "date_due" => $doc->date_due,
                "document_type" => $doc->document_type,
                "document_type_name" => $doc->document_type->name,
                "document_type_slug" => $doc->document_type->slug,
                "document_type_id" => $doc->document_type_id,
                "type" => $doc->document_type->name,
                "status" => $doc->status,
                "created_at" => $doc->created_at,
                "created_by" => $doc->created_by,
                "actor_type" => $doc->actor_type,
                "actor_id" => $doc->actor_id,
            ];
        
    }

    public function enrich(Document $document,  $b = null): array
    {

        $base = !$b ? $this->getBase($document) : $b;

        $type = $document->document_type;

        $handlerClass = $type->enrichment_handler_class;

        if (!$handlerClass) {
    throw new \Exception("Aucun handler d'enrichissement configuré pour le type de document '{$type->name}'");
}

        if (!$handlerClass) {
            // return $base; // fallback ancien système
        }

        $relationName = $document->document_type->relation_name;

    if ($relationName) {
        $document->load($relationName);

        $relationData = $document->{$relationName} ?? null;

        $base[$relationName] = $relationData
            ? $relationData->toArray()
            : null;
    }
        
        $transactions =  $this->user_service_client->getDocumentTransactions($document->id);
        // throw new \Exception(json_encode($transactions));
        $document->transactions = $transactions;


        $handler = app($handlerClass);

        return $handler->enrich($document, $base);
    }
}