<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('expense_categories')->insert([
            ['code' => 'TRANSPORT', 'name' => 'Transport', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'REPAS', 'name' => 'Repas', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'HEBERGEMENT', 'name' => 'Hébergement', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'COMMUNICATION', 'name' => 'Communication', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'DIVERS', 'name' => 'Divers', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}