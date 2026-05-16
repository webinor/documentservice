<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryRulesSeeder extends Seeder
{
    public function run(): void
    {
        // Récupération des IDs dynamiques
        $categories = DB::table('expense_categories')
            ->pluck('id', 'name');

        $rules = [];

        /*
        ======================================================
        🍽 REPAS - TIME BASED
        ======================================================
        */

        // Petit déjeuner
        if (isset($categories['Repas'])) {
            $rules[] = [
                'expense_category_id' => $categories['Repas'],
                'rule_type' => 'TIME_WINDOW',
                'start_time' => '05:00:00',
                'end_time' => '10:00:00',
                'quantity' => 1,
                'priority' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Déjeuner
            $rules[] = [
                'expense_category_id' => $categories['Repas'],
                'rule_type' => 'TIME_WINDOW',
                'start_time' => '12:00:00',
                'end_time' => '14:00:00',
                'quantity' => 1,
                'priority' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Dîner
            $rules[] = [
                'expense_category_id' => $categories['Repas'],
                'rule_type' => 'TIME_WINDOW',
                'start_time' => '19:00:00',
                'end_time' => '22:00:00',
                'quantity' => 1,
                'priority' => 3,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        /*
        ======================================================
        🏨 HÉBERGEMENT - NIGHT RULE
        ======================================================
        */

        if (isset($categories['Hébergement'])) {
            $rules[] = [
                'expense_category_id' => $categories['Hébergement'],
                'rule_type' => 'DAILY',
                'start_time' => null,
                'end_time' => null,
                'quantity' => 1,
                'priority' => 1,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        /*
        ======================================================
        🚫 FIXED / MANUAL (pas de règles automatiques)
        ======================================================
        */

        // Transport, Divers, Taxi etc → aucune règle
        // (gérés via expense_categories.type = MANUAL)

        DB::table('expense_category_rules')->insert($rules);
    }
}