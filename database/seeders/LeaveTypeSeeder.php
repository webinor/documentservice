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
| Permissions exceptionnelles payées
|--------------------------------------------------------------------------
*/

[
    'code' => 'MARRIAGE_EMPLOYEE',
    'name' => 'Mariage du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'SPOUSE_CHILDBIRTH',
    'name' => 'Accouchement de l’épouse du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'CHILD_BAPTISM',
    'name' => 'Baptême d’un enfant du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'CHILD_MARRIAGE',
    'name' => 'Mariage d’un enfant du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'DEATH_SPOUSE',
    'name' => 'Décès du conjoint',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'DEATH_CHILD',
    'name' => 'Décès d’un enfant du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'DEATH_PARENT',
    'name' => 'Décès du père ou de la mère du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'DEATH_PARENT_IN_LAW',
    'name' => 'Décès du père ou de la mère du conjoint légitime',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
    'requires_hr_validation' => true,
],

[
    'code' => 'DEATH_SIBLING',
    'name' => 'Décès du frère ou de la sœur du travailleur',
    'category' => 'EXCEPTIONAL',
    'is_paid' => true,
    'requires_attachment' => true,
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