<?php

namespace App\Services\Mission;

use App\Models\MissionPolicy;


class PolicyScorerService
{
    public function score(MissionPolicy $policy): int
    {
        $score = 0;

        // 1. Position = très spécifique
        if ($policy->position_id) {
            $score += 50;
        }

        // 2. Catégorie = spécifique
        if ($policy->employee_category_id) {
            $score += 30;
        }

        // 3. Scope (international > national > local)
        switch ($policy->scope) {
            case 'INTERNATIONAL':
                $score += 30;
                break;

            case 'NATIONAL':
                $score += 20;
                break;

            case 'LOCAL':
                $score += 10;
                break;

            default:
                $score += 0;
                break;
        }

        // 4. Conditions JSON (bonus intelligence)
        if (!empty($policy->conditions)) {
            $score += 20;
        }

        // 5. temporal rules (start_day etc.)
        if ($policy->start_day) {
            $score += 10;
        }

        return $score;
    }
}