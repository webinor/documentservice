<?php

namespace App\Services\Mission;

use App\Models\Mission;
use App\Models\MissionPolicy;

class MissionAllowanceCalculator
{
    public function calculate(Mission $mission ,  array $employee)
    {
        $matcher = new PolicyMatcherService();
        $resolver = new PolicyResolver();

        $policies = $matcher->match($mission, $employee);

        $policy = $resolver->resolve($policies);

        if (!$policy) {
            return 0;
        }

        return $this->applyPolicy($policy, $mission);
    }


    //     public function calculate(Mission $mission , array $policies)
    // {
    //     $resolver = new PolicyResolver();


    //     $policy = $resolver->resolve($policies);

    //     if (!$policy) {
    //         return 0;
    //     }

    //     return $this->applyPolicy($policy, $mission);
    // }

    private function applyPolicy(MissionPolicy $policy, Mission $mission)
    {
        $days = $mission->duration_days;

        // PER DAY
        if ($policy->calculation_type === 'PER_DAY') {

            $billableDays = 0;

            for ($i = 1; $i <= $days; $i++) {

                if ($policy->start_day && $i < $policy->start_day) {
                    continue;
                }

                if ($policy->end_day && $i > $policy->end_day) {
                    break;
                }

                $billableDays++;
            }

            return $billableDays * $policy->amount;
        }

        // FIXED
        if ($policy->calculation_type === 'FIXED') {
            return $policy->amount;
        }

        // PERCENTAGE
        if ($policy->calculation_type === 'PERCENTAGE') {
            return $mission->budget * ($policy->percentage / 100);
        }

        return 0;
    }
}