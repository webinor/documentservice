<?php

namespace App\Services\Mission;

use App\Models\FinancialTransaction;
use App\Models\Mission;
use App\Models\MissionAdvance;

class MissionAdvanceService
{
    public function calculate(Mission $mission): array
    {
        $advances = $mission->financialTransactions()
            ->whereType("ADVANCE")
            ->orderBy('paid_at', 'desc')
            ->get();

        /**
         * Mapping des avances
         */
        $items = $advances->map(function ($item) {

            return [
                'id' => $item->id,
                'amount' => (float) $item->amount,
                'paid_at' => $item->paid_at,
                'reference' => $item->reference,
                'comment' => $item->comment,
                'status' => $item->status,
                'transaction_id' => $item->transaction_id,
                'validated_at' => $item->validated_at,
                'created_by' => $item->created_by,
                'validated_by' => $item->validated_by,
            ];
        });

        /**
         * Totaux par statut
         */
        $paid = $advances->where('status', 'PAID');
        $pending = $advances->where('status', 'PENDING');
        $cancelled = $advances->where('status', 'CANCELLED');

        $totalPaid = $paid->sum('amount');
        $totalPending = $pending->sum('amount');
        $totalCancelled = $cancelled->sum('amount');

        /**
         * Total global
         */
        $total = $advances->sum('amount');

        return [
            'mission_id' => $mission->id,

            // liste complète
            'items' => $items,

            // totaux globaux
            'total' => $total,

            // breakdown par statut
            'paid' => [
                'count' => $paid->count(),
                'total' => $totalPaid,
                'items' => $paid->values(),
            ],

            'pending' => [
                'count' => $pending->count(),
                'total' => $totalPending,
                'items' => $pending->values(),
            ],

            'cancelled' => [
                'count' => $cancelled->count(),
                'total' => $totalCancelled,
                'items' => $cancelled->values(),
            ],
        ];
    }

    public function markAsPaid(array $payload)
{
    $advance = FinancialTransaction::where('transaction_code', $payload['transaction_code'])->firstOrFail();

    $advance->update([
        'status' => 'PAID',
        'paid_at' => now(),
        'processed_at' => now(),
    ]);
}

}