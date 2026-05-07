<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseLimitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         // Exemple de base
        $repas = DB::table('expense_categories')->where('code', 'REPAS')->first();
        $transport = DB::table('expense_categories')->where('code', 'TRANSPORT')->first();
        $hotel = DB::table('expense_categories')->where('code', 'HEBERGEMENT')->first();

        DB::table('expense_limits')->insert([
            [
                'expense_category_id' => $repas->id,
                'mission_type' => 'SHORT',
                'amount' => 10000,
                'valid_from' => now()->subYear(),
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'expense_category_id' => $transport->id,
                'mission_type' => 'SHORT',
                'amount' => 15000,
                'valid_from' => now()->subYear(),
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'expense_category_id' => $hotel->id,
                'mission_type' => 'LONG',
                'amount' => 50000,
                'valid_from' => now()->subYear(),
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
