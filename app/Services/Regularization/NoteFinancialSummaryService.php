<?php

namespace App\Services\Regularization;

use App\Models\FinancialTransaction;
use App\Models\Misc\Document;
use Exception;

class NoteFinancialSummaryService
{
    public function build(Document $document): array
    {
        $document->load([
            'document_type',
            'fee_note',
        ]);

        if ($document->document_type->slug !== 'note-de-frais') {
            throw new Exception('Document is not a Fee Note.');
        }

        $fee_note = $document->fee_note;

          $transactions = FinancialTransaction::where(
                'transactable_type',
                get_class($fee_note)
            )
            ->where('transactable_id', $fee_note->id)
            ->where('status', 'PAID')
            ->get();

        /**
 * Montant demandé
 */
$requestedAmount = $fee_note->amount;

/**
 * Avance versée
 */
$totalPaid = $transactions
    ->where('transaction_type_code', 'FEE_NOTE_SETTLEMENT')
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