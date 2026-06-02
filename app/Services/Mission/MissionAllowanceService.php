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
        // $items = $allowances->map(function ($item) {

        //     return [
        //         'id' => $item->id,
        //         'allowance_type_id' => $item->allowance_type_id,
        //         'name' => optional($item->allowanceType)->name,
        //         'code' => optional($item->allowanceType)->code,
        //         'quantity' => $item->quantity,
        //         'unit_amount' => (float) $item->unit_amount,
        //         'total' => (float) $item->unit_amount * (int)$item->quantity, //(float) $item->total,
        //         'currency' => $item->currency,
        //         'status' => $item->status,
        //         'calculation_stage' => $item->calculation_stage,
        //         'approved_at' => $item->approved_at,
        //         'paid_at' => $item->paid_at,
        //         'created_at' => $item->created_at,
        //     ];
        // });

        $items = $allowances->map(function ($item) {

    $total = (float) $item->unit_amount * (int) $item->quantity;

    return [
        'id' => $item->id,
        'allowance_type_id' => $item->allowance_type_id,
        'name' => optional($item->allowanceType)->name,
        'code' => optional($item->allowanceType)->code,
        'quantity' => (int) $item->quantity,
        'unit_amount' => (float) $item->unit_amount,
        'total' => $total,
        'currency' => $item->currency,
        'status' => $item->status,
        'calculation_stage' => $item->calculation_stage,
        'approved_at' => $item->approved_at,
        'paid_at' => $item->paid_at,
        'is_estimated' => $item->calculation_stage === 'ESTIMATED',
        'is_final' => $item->calculation_stage === 'FINAL',
    ];
});

$allowancesByType = $items
    ->groupBy('allowance_type_id')
    ->map(function ($group) {

        return [
            'allowance_type_id' => $group->first()['allowance_type_id'],
            'name' => $group->first()['name'],
            'total_estimated' => $group->where('is_estimated', true)->sum('total'),
            'total_final' => $group->where('is_final', true)->sum('total'),
            'variance' =>
                $group->where('is_estimated', true)->sum('total')
                - $group->where('is_final', true)->sum('total'),
        ];
    })
    ->values();

        /**
         * =========================
         * ESTIMATED (prévu)
         * =========================
         */
        // $estimatedItems = $allowances
        //     ->where('calculation_stage', 'ESTIMATED')
        //     ->map(function ($item) {

        //         return [
        //             'id' => $item->id,
        //             'allowance_type_id' => $item->allowance_type_id,
        //             'name' => optional($item->allowanceType)->name,
        //             'code' => optional($item->allowanceType)->code,
        //             'quantity' => $item->quantity,
        //             'unit_amount' => (float) $item->unit_amount,
        //             'total' => (float) $item->unit_amount * (int) $item->quantity,
        //             'currency' => $item->currency,
        //         ];
        //     });
        $estimatedItems = $items
    ->where('calculation_stage', 'ESTIMATED')
    ->values();

        /**
         * =========================
         * FINAL (réel)
         * =========================
         */
        // $finalItems = $allowances
        //     ->where('calculation_stage', 'FINAL')
        //     ->map(function ($item) {

        //         return [
        //             'id' => $item->id,
        //             'allowance_type_id' => $item->allowance_type_id,
        //             'name' => optional($item->allowanceType)->name,
        //             'code' => optional($item->allowanceType)->code,
        //             'quantity' => $item->quantity,
        //             'unit_amount' => (float) $item->unit_amount,
        //             'total' => (float) $item->unit_amount * (int) $item->quantity,
        //             'currency' => $item->currency,
        //             'status' => $item->status,
        //             'approved_at' => $item->approved_at,
        //             'paid_at' => $item->paid_at,
        //         ];
        //     });

        $finalItems = $items
    ->where('calculation_stage', 'FINAL')
    ->values();


        /**
         * =========================
         * Totaux
         * =========================
         */
        $totalEstimated = $estimatedItems->sum('total');
        $totalFinal = $finalItems->sum('total');

        $difference = $totalEstimated - $totalFinal ;

        $variancePercentage = $totalEstimated > 0
    ? ($difference / $totalEstimated) * 100
    : 0;

        return [
    'mission_id' => $mission->id,

    // 🔹 FULL LIST
    'items' => $items,

    // 🔹 PREVISIONNEL
    'estimatedItems' => $estimatedItems,
    'total_estimated' => $totalEstimated,

    // 🔹 REEL
    'finalItems' => $finalItems,
    'total_final' => $totalFinal,

    // 🔹 ECART
    'difference' => $difference,
    'variance_percentage' => round($variancePercentage, 2),

    'allowances_by_type' => $allowancesByType,

    'summary' => [
        'count' => $items->count(),
    ]
];
    }
}