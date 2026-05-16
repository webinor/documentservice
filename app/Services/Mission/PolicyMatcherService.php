<?php

namespace App\Services\Mission;

use App\Models\MissionPolicy;

class PolicyMatcherService
{
    public function match($mission, $employee)
    {
        return MissionPolicy::query()
            ->where('is_active', true)
            ->where(function ($q) use ($employee) {

                $q->whereNull('employee_category_id')
                  ->orWhere('employee_category_id', $employee['employee']['category_id']);
            })
            ->where(function ($q) use ($employee) {

                $q->whereNull('position_id')
                  ->orWhere('position_id', $employee['organization']['position_id']);
            })
            // ->where('scope', $mission->scope)
            ->where('scope', 'NATIONAL')
            ->get();
    }
}