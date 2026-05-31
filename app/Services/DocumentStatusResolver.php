<?php

namespace App\Services;

class DocumentStatusResolver
{
    private $priority = [
        'MISSION_CLOSED' => 1,
        'MISSION_SETTLEMENT' => 2,
        'MISSION_EXPENSE_PAYMENT' => 3,
        'MISSION_EXPENSE_ADVANCE' => 4,
    ];

    public function resolve(array $transactionTypes): string
    {
        $matched = array_intersect(array_keys($this->priority), $transactionTypes);

        if (empty($matched)) {
            return 'UNKNOWN';
        }

        // tri par priorité (plus petit = plus important)
        usort($matched, function ($a, $b) {
            return $this->priority[$a] - $this->priority[$b];
        });

        $top = $matched[0];

        if ($top === 'MISSION_CLOSED') {
            return 'CLOSED';
        }

        if ($top === 'MISSION_SETTLEMENT') {
            return 'MISSION_SETTLEMENT';
        }

        if ($top === 'MISSION_EXPENSE_PAYMENT') {
            return 'PAYMENT_IN_PROGRESS';
        }

        if ($top === 'MISSION_EXPENSE_ADVANCE') {
            return 'MISSION_EXPENSE_ADVANCE';
        }

        return 'UNKNOWN';
    }
}