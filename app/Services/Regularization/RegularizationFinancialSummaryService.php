<?php

namespace App\Services\Regularization;


use App\Models\FinancialTransaction;
use App\Models\Misc\Document;
use Exception;

class RegularizationFinancialSummaryService
{
    public function build(Document $document): array
    {
        // $document = Document::with([
        //     'document_type',
        //     'regularization_sheet',
        // ])->findOrFail($documentId);

        $document->load([
            'document_type',
            'regularization_sheet',
        ]);

        if ($document->document_type->slug !== 'fiche-a-regulariser') {

            throw new Exception('Document is not a regularization sheet.');
        
        }

        $sheet = $document->regularization_sheet;

        /**
         * Montant demandé sur la fiche
         */
        $requestedAmount = (int) $sheet->amount;

           /**
         * Montant reel depensé
         */
        $totalReel = $sheet->items()->sum('total_amount');

        /**
         * Transactions financières
         */
        $transactions = FinancialTransaction::where(
                'transactable_type',
                get_class($sheet)
            )
            ->where(
                'transactable_id',
                $sheet->id
            )
            ->where(
                'status',
                'PAID'
            )
            ->get();

                    /**
         * =========================
         * Totaux par type métier
         * =========================
         */

        $refunds = $transactions->where('adjustment_type', 'REFUND');
        $supplements = $transactions->where('adjustment_type', 'SUPPLEMENT');

        $totalRefund = $refunds->sum('amount');
        $totalSupplement = $supplements->sum('amount');

        /**
         * =========================
         * Impact sur le solde
         * =========================
         * refund => réduit la dette
         * supplement => augmente la dette
         */
        $netImpact = $totalSupplement  - $totalRefund;

        /**
         * Paiements d'avance
         */
        $totalAdvance = $transactions
            ->where('transaction_type_code', 'REGULARIZATION_ADVANCE')
            ->sum('amount');

        /**
         * Régularisations
         */
        $totalSettlement = $transactions
            ->where('transaction_type_code', 'REGULARIZATION_SETTLEMENT')
            ->sum('amount');

        /**
         * Total payé
         */
        $totalPaid =  $totalAdvance + $totalSettlement;

        /**
         * Solde restant
         */

        if ($netImpact > 0) {
            
            $finalBalance = $totalReel - ($totalAdvance + $totalSettlement) ;

        } else {
            
            $finalBalance = $totalReel - ($totalAdvance - $totalSettlement) ;

        }
        

        $initialPaymentCompleted = $totalAdvance > 0;

$hasFinancialMovement = !$initialPaymentCompleted || ( $totalRefund > 0 || $totalSupplement > 0);

$financialMovementCompleted = $hasFinancialMovement
    ? $finalBalance == 0
    : true;

    $regularizationCompleted =
    !$hasFinancialMovement
    ||($initialPaymentCompleted && $finalBalance == 0);

        return [

            "total_prevu" => $requestedAmount,
            "total_reel" => $totalReel,


            'requested_amount' => $requestedAmount,

            'total_expenses'=>$totalReel,

            'total_advance' => $totalAdvance,

            'total_regulation' => $totalSettlement,

            'total_paid' => $totalPaid,

            'final_balance' => $finalBalance,

                // =========================
    // Workflow Action Context
    // =========================
    'has_refund' => $totalRefund > 0,
    'has_supplement' => $totalSupplement > 0,
    'has_financial_movement' => $hasFinancialMovement,
    "initial_payment_completed" => $initialPaymentCompleted,
    "financial_movement_completed" => $financialMovementCompleted,
    "settlement_status" => $this->resolveStatus($finalBalance),
    "regularization_completed" => $regularizationCompleted,

        ];
    }

    protected function resolveStatus(float $remaining): array
    {
        if ($remaining == 0) {
            return [
                'code' => 'SETTLED',
                'label' => 'Régularisée',
                'color' => 'green',
            ];
        }

        if ($remaining > 0) {
            return [
                'code' => 'TO_PAY',
                'label' => 'Paiement restant',
                'color' => 'orange',
            ];
        }

        return [
            'code' => 'OVERPAID',
            'label' => 'Trop payé',
            'color' => 'red',
        ];
    }
}