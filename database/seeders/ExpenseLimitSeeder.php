<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ExpenseLimitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /**
         * ============================================
         * Récupération catégories employés
         * ============================================
         */

        $response = Http::acceptJson()->get(
            config('services.user_service.base_url') . '/employee-categories'
        );

        if (!$response->successful()) {

           $this->command->error(

    json_encode(
        $response->json(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )

);

            return;
        }
        else{
            $this->command->info(
                json_encode($response->json('data', []) , JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

            // return;


        $employeeCategories = $response->json('data', []);

        /**
         * ============================================
         * Récupération catégories dépenses
         * ============================================
         */

        $expenseCategories = DB::table('expense_categories')
            ->get()
            ->keyBy('code');

        /**
         * ============================================
         * Création barèmes
         * ============================================
         */

        foreach ($employeeCategories as $employeeCategory) {

            $employeeCategoryId = $employeeCategory['id'];

            /**
             * ============================================
             * HEBERGEMENT
             * ============================================
             */

            // $hotelAmount = match (strtoupper($employeeCategory['name'])) {

            //     'DIRECTEUR' => 120000,

            //     'CHEF DE SERVICE' => 80000,

            //     'CADRE' => 60000,

            //     default => 40000,
            // };

            $hotelPolicies = [
                'DIRECTEUR' => 120000,
                'CHEF DE SERVICE' => 80000,
                'CADRE' => 60000,
            ];

            $category = strtoupper($employeeCategory['title']);

            $hotelAmount = $hotelPolicies[$category] ?? 40000;

            $this->createLimit(
                $expenseCategories['HOTEL']->id ?? null,
                $employeeCategoryId,
                'SHORT',
                $hotelAmount,
                $now
            );

            /**
             * ============================================
             * BREAKFAST
             * ============================================
             */

            $this->createLimit(
                $expenseCategories['BREAKFAST']->id ?? null,
                $employeeCategoryId,
                'SHORT',
                5000,
                $now
            );

            /**
             * ============================================
             * LUNCH
             * ============================================
             */

            $this->createLimit(
                $expenseCategories['LUNCH']->id ?? null,
                $employeeCategoryId,
                'SHORT',
                10000,
                $now
            );

            /**
             * ============================================
             * DINNER
             * ============================================
             */

            $this->createLimit(
                $expenseCategories['DINNER']->id ?? null,
                $employeeCategoryId,
                'SHORT',
                10000,
                $now
            );

            /**
             * ============================================
             * TAXI
             * ============================================
             */

            $this->createLimit(
                $expenseCategories['TAXI']->id ?? null,
                $employeeCategoryId,
                'SHORT',
                15000,
                $now
            );
        }

        $this->command->info('Expense limits seeded successfully');
    }

    /**
     * ============================================
     * Helper création
     * ============================================
     */

    private function createLimit(
        $expenseCategoryId,
        $employeeCategoryId,
        $missionType,
        $amount,
        $now
    ) {

        if (!$expenseCategoryId) {
            return;
        }

        DB::table('expense_limits')->updateOrInsert(

            [
                'expense_category_id' => $expenseCategoryId,

                'employee_category_id' => $employeeCategoryId,

                'mission_type' => $missionType,
            ],

            [
                'amount' => $amount,

                'valid_from' => $now,

                'valid_to' => null,

                'is_active' => true,

                'updated_at' => $now,

                'created_at' => $now,
            ]
        );
    }
}