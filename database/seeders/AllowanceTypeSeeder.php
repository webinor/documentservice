<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllowanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('allowance_types')->insert([
            [
                'code' => 'PRIME_MISSION',
                'name' => 'Prime de mission',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'PER_DIEM',
                'name' => 'Per diem (indemnité journalière)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'TRANSPORT_ALLOWANCE',
                'name' => 'Indemnité de transport',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RISK_ALLOWANCE',
                'name' => 'Prime de risque',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}