<?php

namespace App\Services\Mission;

use Carbon\Carbon;

class MissionExpenseCalculatorService
{
    public function calculate($departure, $return, $rules)
    {
        $departure = Carbon::parse($departure);
        $return = Carbon::parse($return);

        $result = [];

        $currentDay = $departure->copy()->startOfDay();

        while ($currentDay->lte($return)) {

            foreach ($rules as $rule) {

                if ($rule['rule_type'] === 'TIME_WINDOW') {

                    $count = $this->evaluateTimeWindow(
                        $departure,
                        $return,
                        $currentDay,
                        $rule['start_time'],
                        $rule['end_time']
                    );

                    $this->add($result, $rule['expense_category_id'], $count);
                }

                if ($rule['rule_type'] === 'DAILY') {
                    $this->add($result, $rule['expense_category_id'], 1);
                }
            }

            $currentDay->addDay();
        }

        return $result;
    }

    /**
     * 🔥 CORE LOGIC: datetime overlap check
     */
    private function evaluateTimeWindow($start, $end, $day, $startTime, $endTime)
    {
        if (!$startTime || !$endTime) return 0;

        $windowStart = Carbon::parse($day->format('Y-m-d') . ' ' . $startTime);
        $windowEnd = Carbon::parse($day->format('Y-m-d') . ' ' . $endTime);

        // mission ne touche pas la fenêtre
        if ($end->lte($windowStart) || $start->gte($windowEnd)) {
            return 0;
        }

        return 1;
    }

    private function add(&$result, $categoryId, $value)
    {
        if (!isset($result[$categoryId])) {
            $result[$categoryId] = 0;
        }

        $result[$categoryId] += $value;
    }
}