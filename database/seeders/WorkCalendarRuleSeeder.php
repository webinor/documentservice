<?php

namespace Database\Seeders;

use App\Models\WorkCalendar;
use App\Models\WorkCalendarRule;
use Illuminate\Database\Seeder;

class WorkCalendarRuleSeeder extends Seeder
{
    public function run(): void
    {
        $calendar =
            WorkCalendar::where(
                'is_default',
                true
            )->firstOrFail();


        WorkCalendarRule::updateOrCreate(

            [
                'work_calendar_id' =>
                    $calendar->id,

                'type' =>
                    'ALTERNATE_WEEKLY',

                'day_of_week' =>
                    6,
            ],

            [
                'reference_date' =>
                    '2025-12-27',

                'is_active' =>
                    true,

                'settings' =>
                    [],
            ]

        );
    }
}