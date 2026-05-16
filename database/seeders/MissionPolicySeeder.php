<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AllowanceType;
use App\Models\EmployeeCategory;
use App\Models\Position;
use App\Models\MissionPolicy;

class MissionPolicySeeder extends Seeder
{
    public function run(): void
    {
        $perDiem = AllowanceType::where('code', 'PER_DIEM')->first();
        $primeMission = AllowanceType::where('code', 'PRIME_MISSION')->first();
        $risk = AllowanceType::where('code', 'RISK_ALLOWANCE')->first();

        $executantId =1;// EmployeeCategory::where('code', 'EXECUTANT')->first();
        $cadreId=3;// EmployeeCategory::where('code', 'CADRE')->first();
        $directionId = 5;// EmployeeCategory::where('code', 'DIRECTION')->first();

        $devPositionId = 5;// Position::where('code', 'DG')->first();
        $dgPositionId = 1;

        $policies = [

            /*
            |--------------------------------------------------------------------------
            | PER DIEM NATIONAL - seuil 4 jours
            |--------------------------------------------------------------------------
            */
            [
                'allowance_type_id' => $perDiem->id,
                'scope' => 'NATIONAL',
                'employee_category_id' => $executantId,
                'position_id' => $devPositionId,
                'calculation_type' => 'PER_DAY',
                'amount' => 20000,
                'percentage' => null,
                'start_day' => 3,
                'end_day' => null,
                'apply_after_threshold' => true,
                'conditions' => null,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | PER DIEM INTERNATIONAL
            |--------------------------------------------------------------------------
            */
            [
                'allowance_type_id' => $perDiem->id,
                'scope' => 'INTERNATIONAL',
                'employee_category_id' => null,
                'position_id' => null,
                'calculation_type' => 'PER_DAY',
                'amount' => 80000,
                'percentage' => null,
                'start_day' => 1,
                'end_day' => null,
                'apply_after_threshold' => false,
                'conditions' => null,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | PRIME MISSION - Direction uniquement
            |--------------------------------------------------------------------------
            */
            [
                'allowance_type_id' => $primeMission->id,
                'scope' => 'LOCAL',
                'employee_category_id' => $directionId,
                'position_id' => null,
                'calculation_type' => 'FIXED',
                'amount' => 150000,
                'percentage' => null,
                'start_day' => null,
                'end_day' => null,
                'apply_after_threshold' => false,
                'conditions' => json_encode([
                    'requires_approval' => 'DG'
                ]),
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | PRIME RISQUE - exécutant terrain
            |--------------------------------------------------------------------------
            */
            [
                'allowance_type_id' => $risk->id,
                'scope' => 'INTERNATIONAL',
                'employee_category_id' => $executantId,
                'position_id' => null,
                'calculation_type' => 'PER_DAY',
                'amount' => 50000,
                'percentage' => null,
                'start_day' => 1,
                'end_day' => null,
                'apply_after_threshold' => false,
                'conditions' => json_encode([
                    'risk_level' => 'HIGH'
                ]),
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | PRIME MISSION - DG fixe
            |--------------------------------------------------------------------------
            */
            [
                'allowance_type_id' => $primeMission->id,
                'scope' => 'LOCAL',
                'employee_category_id' => $directionId,
                'position_id' => $dgPositionId,
                'calculation_type' => 'FIXED',
                'amount' => 250000,
                'percentage' => null,
                'start_day' => null,
                'end_day' => null,
                'apply_after_threshold' => false,
                'conditions' => json_encode([
                    'role' => 'DG'
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($policies as $policy) {
            MissionPolicy::updateOrCreate(
                [
                    'allowance_type_id' => $policy['allowance_type_id'],
                    'scope' => $policy['scope'],
                    'employee_category_id' => $policy['employee_category_id'],
                    'position_id' => $policy['position_id'],
                ],
                $policy
            );
        }
    }
}