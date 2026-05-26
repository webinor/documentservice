<?php

namespace App\Services\Mission;

use App\Models\Mission;

class MissionAllowanceService
{
    public function calculate(Mission $mission): array
    {
        $allowances = $mission->allowances()
            ->with('allowanceType')
            ->orderBy('id', 'desc')
            ->get();

         /**
         * Mapping propre
         */
        $items = $allowances->map(function ($item) {

            return [
                'id' => $item->id,
                'allowance_type_id' => $item->allowance_type_id,
                'name' => optional($item->allowanceType)->name,
                'code' => optional($item->allowanceType)->code,
                'quantity' => $item->quantity,
                'unit_amount' => (float) $item->unit_amount,
                'total' => (float) $item->unit_amount * (int)$item->quantity, //(float) $item->total,
                'currency' => $item->currency,
                'status' => $item->status,
                'calculation_stage' => $item->calculation_stage,
                'approved_at' => $item->approved_at,
                'paid_at' => $item->paid_at,
                'created_at' => $item->created_at,
            ];
        });

        /**
         * =========================
         * ESTIMATED (prévu)
         * =========================
         */
        $estimatedItems = $allowances
            ->where('calculation_stage', 'ESTIMATED')
            ->map(function ($item) {

                return [
                    'id' => $item->id,
                    'allowance_type_id' => $item->allowance_type_id,
                    'name' => optional($item->allowanceType)->name,
                    'code' => optional($item->allowanceType)->code,
                    'quantity' => $item->quantity,
                    'unit_amount' => (float) $item->unit_amount,
                    'total' => (float) $item->unit_amount * (int) $item->quantity,
                    'currency' => $item->currency,
                ];
            });

        /**
         * =========================
         * FINAL (réel)
         * =========================
         */
        $finalItems = $allowances
            ->where('calculation_stage', 'FINAL')
            ->map(function ($item) {

                return [
                    'id' => $item->id,
                    'allowance_type_id' => $item->allowance_type_id,
                    'name' => optional($item->allowanceType)->name,
                    'code' => optional($item->allowanceType)->code,
                    'quantity' => $item->quantity,
                    'unit_amount' => (float) $item->unit_amount,
                    'total' => (float) $item->unit_amount * (int) $item->quantity,
                    'currency' => $item->currency,
                    'status' => $item->status,
                    'approved_at' => $item->approved_at,
                    'paid_at' => $item->paid_at,
                ];
            });

        /**
         * =========================
         * Totaux
         * =========================
         */
        $totalEstimated = $estimatedItems->sum('total');
        $totalFinal = $finalItems->sum('total');

        $difference = $totalEstimated - $totalFinal ;

        return [
            'mission_id' => $mission->id,

            'allowances' => $items,
            

            /**
             * Prévu
             */
            'total_estimated' => $totalEstimated,
            'estimated_allowances' => $estimatedItems,

            /**
             * Réel
             */
            'total_final' => $totalFinal,
            'final_allowances' => $finalItems,

            /**
             * Écart
             */
            'difference' => $difference,
        ];
    }
}