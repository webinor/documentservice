<?php

namespace App\Services\Mission;

use App\Models\Mission;

class MissionAdvanceService
{
    public function calculate(Mission $mission): array
    {
        $advances = $mission->advances()
            ->orderBy('payment_date', 'desc')
            ->get();

        /**
         * Mapping des avances
         */
        $items = $advances->map(function ($item) {

            return [
                'id' => $item->id,
                'amount' => (float) $item->amount,
                'payment_date' => $item->payment_date,
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
        $paid = $advances->where('status', 'paid');
        $pending = $advances->where('status', 'pending');
        $cancelled = $advances->where('status', 'cancelled');

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
}