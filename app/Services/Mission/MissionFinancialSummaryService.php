<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use App\Services\Financial\PurchaseFinancialSummaryService;
use App\Services\Regularization\NoteFinancialSummaryService;
use App\Services\Regularization\RegularizationFinancialSummaryService;
use App\Services\Regularization\TaxiFinancialSummaryService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MissionFinancialSummaryService
{



 protected PurchaseFinancialSummaryService $purchase_financial_summary_service;

    public function __construct(
        PurchaseFinancialSummaryService $purchase_financial_summary_service
    ) {
        $this->purchase_financial_summary_service = $purchase_financial_summary_service;
    }
    /**
     * Construire le résumé financier complet
     */
    public function build( $documentId): array
    {
        /**
         * Document principal
         */
        // $document = Document::with("document_type")->findOrFail($documentId);
         if (Str::isUuid($documentId)) {
    $document = Document::where('uuid', $documentId)->firstOrFail();
} else {
    $document = Document::findOrFail($documentId);
}



        /**
         * Relation métier mission
         */
        $mission = $document->mission;

        // return

        $expenses = app(MissionExpenseService::class)->calculate($mission);

        /**
         * ============================
         * 1️⃣ Budget prévu
         * ============================
         */
        $totalPrevu = (float) $expenses["total_prevu"];

        /**
         * ============================
         * 2️⃣ Dépenses réelles
         * ============================
         */
        $totalReel = $expenses["total_reel"];

        /**
         * ============================
         * 3️⃣ Indemnités
         * ============================
         */

        $allowances = app(MissionAllowanceService::class)->calculate($mission);

        $totalAllowances = $allowances["total_final"];
        // $totalAllowances =0;//  $this->fetchAllowances($documentId);

        /**
         * ============================
         * 4️⃣ Avances déjà reçues
         * ============================
         */
        $totalAdvance =app(MissionAdvanceService::class)->calculate($mission)['total'];//  $totalPrevu; //  $this->fetchAdvances($documentId);


        $regulation = app(MissionRegulationService::class)->calculate($mission);


           $totalRefund = $regulation['refunds']['total'];
        $totalSupplement = $regulation['supplements']['total'];// $supplements->sum('amount');


        $totalRegulation = $regulation['total'];

        $net_impact = $regulation['net_impact'];

        // throw new Exception(json_encode( $regulation['net_impact']), 1);
        

        /**
         * ============================
         * 5️⃣ Calcul balance finale
         * ============================
         *
         * positif :
         * société doit rembourser agent
         *
         * négatif :
         * agent doit rembourser société
         */

            if ($net_impact > 0) {
            
            $finalBalance = $totalReel - ($totalAdvance + $totalRegulation) ;

        } else {
            
            $finalBalance = $totalReel - ($totalAdvance - $totalRegulation) ;

        }

       $initialPaymentCompleted = $totalAdvance > 0;


$hasFinancialMovement =$initialPaymentCompleted && $finalBalance != 0 ; 

$financialMovementCompleted = $hasFinancialMovement ? $finalBalance == 0 : true;

$regularizationCompleted = !$hasFinancialMovement || ( $initialPaymentCompleted && $finalBalance == 0 );

        /**
         * ============================
         * 6️⃣ Statut métier
         * ============================
         */
        $settlementStatus = $this->resolveSettlementStatus($finalBalance);

        return [
            "total_prevu" => $totalPrevu,
            "total_reel" => $totalReel,
            "total_allowances" => $totalAllowances,
            "total_advance" => $totalAdvance,
            "total_regulation" => $totalRegulation,
            "final_balance" => $finalBalance,
            "settlement_status" => $settlementStatus,


              'has_refund' => $totalRefund > 0,
    'has_supplement' => $totalSupplement > 0,
    'has_financial_movement' => $hasFinancialMovement,
    "initial_payment_completed" => $initialPaymentCompleted,
    "financial_movement_completed" => $financialMovementCompleted,
    // "settlement_status" => $this->resolveStatus($finalBalance),
    "regularization_completed" => $regularizationCompleted,
        ];
    }

    /**
     * ===================================
     * Dépenses réelles
     * ===================================
     */
    protected function fetchRealExpenses(int $documentId): float
    {
        $url =
            config("services.finance_service.base_url") .
            "/missions/{$documentId}/real-expenses";

        $response = Http::withHeaders($this->gatewayHeaders())->get($url);

        if (!$response->successful()) {
            return 0;
        }

        return (float) ($response->json()["amount"] ?? 0);
    }

    /**
     * ===================================
     * Indemnités
     * ===================================
     */
    protected function fetchAllowances(int $documentId): float
    {
        $url =
            config("services.finance_service.base_url") .
            "/missions/{$documentId}/allowances";

        $response = Http::withHeaders($this->gatewayHeaders())->get($url);

        if (!$response->successful()) {
            return 0;
        }

        return (float) ($response->json()["amount"] ?? 0);
    }

    /**
     * ===================================
     * Avances
     * ===================================
     */
    protected function fetchAdvances(int $documentId): float
    {
        $url =
            config("services.finance_service.base_url") .
            "/missions/{$documentId}/advances";

        $response = Http::withHeaders($this->gatewayHeaders())->get($url);

        if (!$response->successful()) {
            return 0;
        }

        return (float) ($response->json()["amount"] ?? 0);
    }

    /**
     * ===================================
     * Résolution du statut
     * ===================================
     */
    protected function resolveSettlementStatus(float $balance): string
    {
        if ($balance == 0) {
            return "REGULARIZED";
        }

        if ($balance > 0) {
            return "COMPANY_OWES_AGENT";
        }

        return "AGENT_OWES_COMPANY";
    }

    /**
     * ===================================
     * Headers gateway
     * ===================================
     */
    protected function gatewayHeaders(): array
    {
        return [
            "Authorization" => "Bearer " . request()->bearerToken(),

            "Accept" => "application/json",
        ];
    }
}
