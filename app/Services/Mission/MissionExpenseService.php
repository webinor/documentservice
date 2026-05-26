<?php

namespace App\Services\Mission;

use App\Models\Mission;
use Illuminate\Support\Facades\DB;

class MissionExpenseService
{
    public function calculate(Mission $mission): array
    {
        $mission->load(
            'mission_expenses.expense_category'
        );

        $expenses = $mission->mission_expenses;

        /**
         * ===============================
         * Périodes prévues
         * ===============================
         */
        $plannedStart = $this->buildDateTime(
            $mission->departure_date_base_planned,
            $mission->departure_time_base_planned
        );

        $plannedEnd = $this->buildDateTime(
            $mission->arrival_date_base_planned,
            $mission->arrival_time_base_planned
        );

        /**
         * ===============================
         * Périodes réelles
         * ===============================
         */
        $actualStart = (
            $mission->departure_date_base_actual &&
            $mission->departure_time_base_actual
        )
            ? $this->buildDateTime(
                $mission->departure_date_base_actual,
                $mission->departure_time_base_actual
            )
            : null;

        $actualEnd = (
            $mission->arrival_date_base_actual &&
            $mission->arrival_time_base_actual
        )
            ? $this->buildDateTime(
                $mission->arrival_date_base_actual,
                $mission->arrival_time_base_actual
            )
            : null;

        /**
         * ===============================
         * Règles
         * ===============================
         */
        $rules = DB::table('expense_category_rules')
            ->whereIn(
                'expense_category_id',
                $expenses->pluck('expense_category_id')
            )
            ->get()
            ->groupBy('expense_category_id');

        /**
         * ===============================
         * Quantités prévues
         * ===============================
         */
        $plannedResult = collect(
            app(MissionExpenseCalculatorService::class)
                ->calculate(
                    $plannedStart,
                    $plannedEnd,
                    $rules->flatten()
                )
        )->keyBy('expense_category_id');

        /**
         * ===============================
         * Quantités réelles
         * ===============================
         */
        $actualResult =
            ($actualStart && $actualEnd)
                ? collect(
                    app(MissionExpenseCalculatorService::class)
                        ->calculate(
                            $actualStart,
                            $actualEnd,
                            $rules->flatten()
                        )
                )->keyBy('expense_category_id')
                : collect();

        /**
         * ===============================
         * Quantités déclarées
         * ===============================
         */
        $manualDeclaredQuantities = $expenses
            ->where('type', 'DECLAREE')
            ->groupBy('expense_category_id');

        /**
         * ===============================
         * Enrichissement
         * ===============================
         */
        $expenses = $expenses->map(function (
            $expense
        ) use (
            $plannedResult,
            $actualResult,
            $manualDeclaredQuantities
        ) {




            $categoryId =
                $expense->expense_category_id;

            $manualQuantity = collect(
                $manualDeclaredQuantities[$categoryId]
                ?? []
            )->sum('quantity');


            /**
             * quantité réellement retenue
             */
            // $expense->final_quantity_manual =
            //     $manualQuantity ?: $final;




            if ($expense->type === 'PREVISIONNELLE') {

                $planned =
                $plannedResult[$categoryId]['quantity']
                ?? 0;

                   $expense->planned_quantity =
                $planned;


                   /**
             * total prévu
             */
            $expense->planned_total =
                $planned * $expense->amount;
            
            }


            if ($expense->type === 'DECLAREE') {


                $final =
                $actualResult[$categoryId]['quantity']
                ?? $planned;


                  $expense->final_quantity =
                $final;



            /**
             * total réel
             */
            $expense->final_total =
                ($manualQuantity ?: $final)
                * $expense->amount;
            
            }


   

            return $expense;
        });

        /**
         * ===============================
         * Totaux globaux
         * ===============================
         */
        $totalPrevu =
            $expenses->sum('planned_total');

        $totalReel =
            $expenses->sum('final_total');

        return [
            'mission_id' => $mission->id,
            'expenses' => $expenses,
            'total_prevu' => $totalPrevu,
            'total_reel' => $totalReel,
        ];
    }

    /**
     * ===================================
     * Build datetime
     * ===================================
     */
    protected function buildDateTime(
        $date,
        $time
    ) {
        return \Carbon\Carbon::parse(
            "{$date} {$time}"
        );
    }
}