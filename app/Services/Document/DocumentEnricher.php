<?php

namespace App\Services\Document;

use App\Services\UserServiceClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentEnricher{


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





    
          
}