<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    public function run()
    {
        $leaveTypes = [

            /*
            |--------------------------------------------------------------------------
            | Congés légaux
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'ANNUAL',
                'name' => 'Congé annuel payé',
                'category' => 'ANNUAL',
                // 'default_days' => 30,
                'is_paid' => true,
                'requires_attachment' => false,
                'requires_hr_validation' => true,
            ],

            [
                'code' => 'SICK',
                'name' => 'Congé maladie',
                'category' => 'SICK',
                // 'default_days' => null,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],

            [
                'code' => 'MATERNITY',
                'name' => 'Congé maternité',
                'category' => 'MATERNITY',
                // 'default_days' => 98,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],

            [
                'code' => 'PATERNITY',
                'name' => 'Congé paternité',
                'category' => 'PATERNITY',
                // 'default_days' => 10,
                'is_paid' => true,
                'requires_attachment' => false,
                'requires_hr_validation' => true,
            ],


            /*
            |--------------------------------------------------------------------------
            | Permissions exceptionnelles payées
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'MARRIAGE_EMPLOYEE',
                'name' => 'Mariage du salarié',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 3,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            [
                'code' => 'BIRTH_CHILD',
                'name' => 'Naissance d’un enfant',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 3,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            [
                'code' => 'DEATH_SPOUSE',
                'name' => 'Décès du conjoint',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 5,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            [
                'code' => 'DEATH_CHILD',
                'name' => 'Décès d’un enfant',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 5,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            [
                'code' => 'DEATH_PARENT',
                'name' => 'Décès père ou mère',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 3,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            [
                'code' => 'FAMILY_EVENT',
                'name' => 'Événement familial exceptionnel',
                'category' => 'EXCEPTIONAL',
                // 'default_days' => 1,
                'is_paid' => true,
                'requires_attachment' => true,
                'requires_hr_validation' => true,
            ],


            /*
            |--------------------------------------------------------------------------
            | Congés non payés
            |--------------------------------------------------------------------------
            */

            [
                'code' => 'UNPAID',
                'name' => 'Congé sans solde',
                'category' => 'UNPAID',
                // 'default_days' => null,
                'is_paid' => false,
                'requires_attachment' => false,
                'requires_hr_validation' => true,
            ],

        ];


        foreach ($leaveTypes as $leaveType) {

            LeaveType::updateOrCreate(
                [
                    'code' => $leaveType['code']
                ],
                $leaveType
            );

        }
    }
}