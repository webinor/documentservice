<?php

namespace App\Services\Mission;

use App\Models\Mission;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class MissionExpenseAmountService
{
    protected $calculatorService;

    public function __construct(
        MissionExpenseCalculatorService $calculatorService
    ) {
        $this->calculatorService = $calculatorService;
    }

    public function calculateTotals(Mission $mission): array
    {
        $mission->load('mission_expenses.expense_category');

        $expenses = $mission->mission_expenses;

        /**
         * 🔥 Construction périodes
         */
        $plannedStart = Carbon::parse(
            $mission->departure_date_base_planned . ' ' .
            $mission->departure_time_base_planned
        );

        $plannedEnd = Carbon::parse(
            $mission->arrival_date_base_planned . ' ' .
            $mission->arrival_time_base_planned
        );

        $actualStart = (
            $mission->departure_date_base_actual &&
            $mission->departure_time_base_actual
        )
            ? Carbon::parse(
                $mission->departure_date_base_actual . ' ' .
                $mission->departure_time_base_actual
            )
            : null;

        $actualEnd = (
            $mission->arrival_date_base_actual &&
            $mission->arrival_time_base_actual
        )
            ? Carbon::parse(
                $mission->arrival_date_base_actual . ' ' .
                $mission->arrival_time_base_actual
            )
            : null;

        /**
         * 🔥 Règles
         */
        $rules = DB::table('expense_category_rules')
            ->whereIn(
                'expense_category_id',
                $expenses->pluck('expense_category_id')
            )
            ->get()
            ->groupBy('expense_category_id');

        /**
         * 🔥 Quantités prévues
         */
        $plannedQuantities = collect(
            $this->calculatorService->calculate(
                $plannedStart,
                $plannedEnd,
                $rules->flatten()
            )
        )->keyBy('expense_category_id');

        // throw new Exception(json_encode($plannedQuantities), 1);


        /**
         * 🔥 Quantités réelles
         */
        $finalQuantities = ($actualStart && $actualEnd)
            ? collect(
                $this->calculatorService->calculate(
                    $actualStart,
                    $actualEnd,
                    $rules->flatten()
                )
            )->keyBy('expense_category_id')
            : 0;//$plannedQuantities;

        // throw new Exception(json_encode($finalQuantities), 1);


        $plannedTotal = 0;
        $finalTotal = 0;

        foreach ($expenses as $expense) {

        // throw new Exception(json_encode($expenses->pluck("amount")), 1);
        

            $categoryId = $expense->expense_category_id;

               /**
             * 🔥 Montant unitaire
             */
            // $unitAmount = $expense->unit_amount ?? 0;
            $unitAmount = $expense->amount ?? 0;

            if ($expense->type === 'PREVISIONNELLE') {
                
            $plannedQuantity =
                $plannedQuantities[$categoryId]['quantity'] ?? 0;

            
            $plannedTotal += (
                $plannedQuantity * $unitAmount
            );

            }


            if ($expense->type === 'DECLAREE') {
               
              $finalQuantity =
                $finalQuantities[$categoryId]['quantity']
                ?? 0;// $plannedQuantity;


            $finalTotal += (
                $finalQuantity * $unitAmount
            );
            }


          

         

          

        }

        return [

            'planned_total' => $plannedTotal,

            'final_total' => $finalTotal,

            'difference' => (
                $finalTotal - $plannedTotal
            )
        ];
    }
}