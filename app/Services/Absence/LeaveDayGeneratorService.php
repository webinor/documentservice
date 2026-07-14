<?php

namespace App\Services\Absence;

use App\Models\AbsenceRequest;
use App\Models\LeaveRequestDay;

class LeaveDayGeneratorService
{

    public function generate(
        AbsenceRequest $absence,
        array $simulation
    ): void
    {

        
        
        // throw new \Exception(json_encode($simulation['days']), 1);

        foreach ($simulation['days'] as $day) {


            LeaveRequestDay::create([

                'absence_request_id' => $absence->id,

                'date' => $day['date'],

                'leave_type_id' => $absence->leave_type_id,

                'coverage_type' => $day['coverage_type'],

                'deducts_balance' => $day['deducts_balance'],

                'deduct_days' => $day['deduct_days'],

                'is_non_working_day' => !$day['is_working_day'],

                'comment' => $day['comment'],

            ]);

        }

    }

}