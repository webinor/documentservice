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
| Permissions exceptionnelles
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Mariage du travailleur — 4 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'MARRIAGE_EMPLOYEE',
    'max_days' => 4,
    'paid_days' => 4,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Accouchement de l'épouse — 3 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'SPOUSE_CHILDBIRTH',
    'max_days' => 3,
    'paid_days' => 3,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Baptême d'un enfant — 1 jour
|--------------------------------------------------------------------------
*/
[
    'code' => 'CHILD_BAPTISM',
    'max_days' => 1,
    'paid_days' => 1,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Mariage d'un enfant — 2 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'CHILD_MARRIAGE',
    'max_days' => 2,
    'paid_days' => 2,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Décès du conjoint — 5 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'DEATH_SPOUSE',
    'max_days' => 5,
    'paid_days' => 5,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Décès d'un enfant — 3 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'DEATH_CHILD',
    'max_days' => 3,
    'paid_days' => 3,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Décès du père ou de la mère — 5 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'DEATH_PARENT',
    'max_days' => 5,
    'paid_days' => 5,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Décès du père ou de la mère du conjoint légitime — 3 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'DEATH_PARENT_IN_LAW',
    'max_days' => 3,
    'paid_days' => 3,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
],

/*
|--------------------------------------------------------------------------
| Décès du frère ou de la sœur — 3 jours
|--------------------------------------------------------------------------
*/
[
    'code' => 'DEATH_SIBLING',
    'max_days' => 3,
    'paid_days' => 3,
    'deduct_excess_days' => false,
    'uses_balance' => false,
    'allow_split' => false,
    'settings' => [
        'count_weekends' => false,
        'count_public_holidays' => false,
    ],
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