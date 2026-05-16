<?php

namespace App\Services\Audit;

class AuditService
{
    public function log($mission, $employee, $policy, $amount)
    {
        // MissionAllowanceLog::create([
        //     'mission_id' => $mission->id,
        //     'employee_id' => $employee->id,
        //     'mission_policy_id' => $policy?->id,
        //     'amount' => $amount,
        //     'calculated_at' => now(),
        // ]);
    }
}