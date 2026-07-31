<?php

namespace App\Services\Regularization;

use App\Models\FinancialTransaction;
use App\Models\Misc\Document;
use Exception;

class TaxiFinancialSummaryService
{
    public function build(Document $document): array
    {
        $document->load([
            'document_type',
            'taxi_paper',
        ]);

        if ($document->document_type->slug !== 'papier-taxi') {
            throw new Exception('Document is not a Taxi Paper.');
        }

        $taxi = $document->taxi_paper;

          $transactions = FinancialTransaction::where(
                'transactable_type',
                get_class($taxi)
            )
            ->where('transactable_id', $taxi->id)
            ->where('status', 'PAID')
            ->get();

        /**
 * Montant demandé
 */
$requestedAmount = collect($taxi->rides)->sum('montant');

/**
 * Avance versée
 */
$totalPaid = $transactions
    ->where('transaction_type_code', 'TAXI_PAPER_SETTLEMENT')
    ->sum('amount');

/**
 * Solde
 */
$finalBalance = $requestedAmount - $totalPaid;

$paymentCompleted = abs($finalBalance) < 0.01;

       return [
    'requested_amount' => $requestedAmount,

    'total_advance' => $totalPaid,
    'total_paid' => $totalPaid,

    'final_balance' => $finalBalance,

    'payment_completed' => $paymentCompleted,

    'signatureCompleted' => $finalBalance == 0,

    'settlement_status' => $this->resolveStatus($finalBalance),
];
    }

    protected function resolveStatus(float $remaining): array
    {
        if (abs($remaining) < 0.01) {
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
            'code' => 'TO_REFUND',
            'label' => 'Remboursement attendu',
            'color' => 'red',
        ];
    }
}