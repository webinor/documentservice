<?php

namespace App\Services\Regularization;


use App\Models\FinancialTransaction;
use App\Models\Misc\Document;
use Exception;

class RegularizationFinancialSummaryService
{
    public function build(int $documentId): array
    {
        $document = Document::with([
            'document_type',
            'regularization_sheet',
        ])->findOrFail($documentId);

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
        $finalBalance = $totalReel - $totalAdvance + $totalSettlement ;

        return [

            "total_prevu" => $requestedAmount,
            "total_reel" => $totalReel,


            'requested_amount' => $requestedAmount,

            'total_expenses'=>$totalReel,

            'total_advance' => $totalAdvance,

            'total_regulation' => $totalSettlement,

            'total_paid' => $totalPaid,

            'final_balance' => $finalBalance,

            'settlement_status' => $this->resolveStatus($finalBalance),

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