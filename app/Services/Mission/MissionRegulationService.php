<?php

namespace App\Services\Mission;

use App\Models\FinancialTransaction;
use App\Models\Mission;
use App\Models\MissionRegulation;
use Illuminate\Support\Str;

class MissionRegulationService
{
    public function calculate(Mission $mission): array
    {
        $regulations = $mission->financialTransactions()
            ->whereType("SETTLEMENT")
            ->orderBy('created_at', 'desc')
            ->get();

        /**
         * =========================
         * Mapping des régulations
         * =========================
         */
        $items = $regulations->map(function ($item) {

            return [
                'id' => $item->id,
                'mission_id' => $item->mission_id,

                'type' => Str::lower($item->adjustment_type), // refund | supplement
                'amount' => (float) $item->amount,

                'balance_before' => (float) $item->balance_before,
                'balance_after' => (float) $item->balance_after,

                'payment_method' => $item->payment_method,
                'reference' => $item->reference,
                'comment' => $item->comment,

                'status' => $item->status,

                'transaction_id' => $item->transaction_id,
                'created_by' => $item->created_by,
                'processed_at' => $item->processed_at,

                'created_at' => $item->created_at,
            ];
        });

        /**
         * =========================
         * Séparation par statut
         * =========================
         */
        $pending = $regulations->where('status', 'PENDING');
        $processed = $regulations->where('status', 'PAID');
        $cancelled = $regulations->where('status', 'CANCELLED');

        /**
         * =========================
         * Totaux globaux
         * =========================
         */
        $totalAmount = $regulations->sum('amount');

        /**
         * =========================
         * Totaux par type métier
         * =========================
         */

        $refunds = $regulations->where('adjustment_type', 'REFUND');
        $supplements = $regulations->where('adjustment_type', 'SUPPLEMENT');

        $totalRefund = $refunds->sum('amount');
        $totalSupplement = $supplements->sum('amount');

        /**
         * =========================
         * Impact sur le solde
         * =========================
         * refund => réduit la dette
         * supplement => augmente la dette
         */
        $netImpact = $totalRefund - $totalSupplement;

        return [
            'mission_id' => $mission->id,

            /**
             * liste complète
             */
            'items' => $items,

            /**
             * totaux globaux
             */
            'total' => $totalAmount,

            /**
             * breakdown statut
             */
            'pending' => [
                'count' => $pending->count(),
                'total' => $pending->sum('amount'),
                'items' => $pending->values(),
            ],

            'processed' => [
                'count' => $processed->count(),
                'total' => $processed->sum('amount'),
                'items' => $processed->values(),
            ],

            'cancelled' => [
                'count' => $cancelled->count(),
                'total' => $cancelled->sum('amount'),
                'items' => $cancelled->values(),
            ],

            /**
             * breakdown métier
             */
            'refunds' => [
                'count' => $refunds->count(),
                'total' => $totalRefund,
                'items' => $refunds->values(),
            ],

            'supplements' => [
                'count' => $supplements->count(),
                'total' => $totalSupplement,
                'items' => $supplements->values(),
            ],

            /**
             * impact financier net
             */
            'net_impact' => $netImpact,

            /**
             * résumé comptable
             */
            'summary' => [
                'is_balanced' => $netImpact === 0,
                'status' => $netImpact === 0
                    ? 'BALANCED'
                    : ($netImpact > 0 ? 'SURPLUS' : 'DEFICIT'),
            ],
        ];
    }

    public function markAsPaid(array $payload)
{
    $regulation = FinancialTransaction::where('transaction_code', $payload['transaction_code'])->firstOrFail();

    $regulation->update([
        'status' => 'PAID',
        'paid_at' => now(),
        'processed_at' => now(),
    ]);
}
}