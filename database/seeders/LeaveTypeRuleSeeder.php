<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;
use App\Models\LeaveTypeRule;

class LeaveTypeRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $rules = [

            /*
            |--------------------------------------------------------------------------
            | Congé annuel
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'ANNUAL',
                'max_days' => 30,
                'paid_days' => null,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => true,
                'settings' => [
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                    'allow_half_day' => true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Congé maladie
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'SICK',
                'max_days' => null,
                'paid_days' => null,
                'deduct_excess_days' => false,
                'uses_balance' => false,
                'allow_split' => true,
                'settings' => [
                    'medical_certificate_required' => true,
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Congé maternité
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'MATERNITY',
                'max_days' => 98,
                'paid_days' => 98,
                'deduct_excess_days' => false,
                'uses_balance' => false,
                'allow_split' => false,
                'settings' => [
                    'count_weekends' => true,
                    'count_public_holidays' => true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Congé paternité
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'PATERNITY',
                'max_days' => 10,
                'paid_days' => 10,
                'deduct_excess_days' => false,
                'uses_balance' => false,
                'allow_split' => false,
                'settings' => [
                    'count_weekends' => true,
                    'count_public_holidays' => true,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Mariage du salarié
            | 4 jours payés puis déduction du surplus
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'MARRIAGE_EMPLOYEE',
                'max_days' => null,
                'paid_days' => 4,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Naissance
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'BIRTH_CHILD',
                'max_days' => null,
                'paid_days' => 3,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Décès conjoint
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'DEATH_SPOUSE',
                'max_days' => null,
                'paid_days' => 5,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Décès enfant
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'DEATH_CHILD',
                'max_days' => null,
                'paid_days' => 5,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Décès père / mère
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'DEATH_PARENT',
                'max_days' => null,
                'paid_days' => 3,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Evènement familial
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'FAMILY_EVENT',
                'max_days' => null,
                'paid_days' => 1,
                'deduct_excess_days' => true,
                'uses_balance' => true,
                'allow_split' => false,
                'settings' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | Congé sans solde
            |--------------------------------------------------------------------------
            */
            [
                'code' => 'UNPAID',
                'max_days' => null,
                'paid_days' => 0,
                'deduct_excess_days' => false,
                'uses_balance' => false,
                'allow_split' => true,
                'settings' => [],
            ],

        ];

        foreach ($rules as $rule) {

            $leaveType = LeaveType::where('code', $rule['code'])->first();

            if (!$leaveType) {
                continue;
            }

            LeaveTypeRule::updateOrCreate(

                [
                    'leave_type_id' => $leaveType->id,
                ],

                [
                    'max_days'             => $rule['max_days'],
                    'paid_days'            => $rule['paid_days'],
                    'deduct_excess_days'   => $rule['deduct_excess_days'],
                    'uses_balance'         => $rule['uses_balance'],
                    'allow_split'          => $rule['allow_split'],
                    'settings'             => $rule['settings'],
                ]

            );
        }
    }
}