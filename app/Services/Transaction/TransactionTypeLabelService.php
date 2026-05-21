<?php

namespace App\Services\Transaction;


class TransactionTypeLabelService
{
    const LABELS = [

        'MISSION_EXPENSE_ADVANCE'
            => 'Avance sur frais de mission',

        'MISSION_SETTLEMENT'
            => 'Régularisation mission',

        'MISSION_REIMBURSEMENT'
            => 'Remboursement frais mission',

        'MISSION_REFUND'
            => 'Remboursement entreprise',

    ];

    /**
     * Retourne le libellé d'un type de transaction
     */
    public function getLabel(string $code): string
    {
        return self::LABELS[$code]
            ?? 'Transaction inconnue';
    }
}