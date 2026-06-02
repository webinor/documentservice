<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use Illuminate\Support\Facades\Http;
use Exception;

class MissionFinancialSummaryService
{
    /**
     * Construire le résumé financier complet
     */
    public function build(int $documentId): array
    {
        /**
         * Document principal
         */
        $document = Document::with("document_type")->findOrFail($documentId);

        /**
         * Vérification type mission
         */
        if ($document->document_type->slug !== "mission") {
            throw new Exception("Document is not a mission.");
        }

        /**
         * Relation métier mission
         */
        $mission = $document->mission;

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
        $totalRegulation = $regulation['total'];

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
        $finalBalance = $totalReel + $totalAllowances - $totalAdvance   + $regulation['net_impact']; ;

        //         $totalOut =
        //     $totalReel
        //     + max(0, $totalAllowances);

        // $totalIn =
        //     $totalAdvance
        //     + max(0, -$totalAllowances);

        // $finalBalance = $totalOut - $totalIn;

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
