<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkCalendar;
use App\Models\WorkCalendarWorkingDay;

class WorkCalendarWorkingDaySeeder extends Seeder
{
    public function run()
    {
        $calendar = WorkCalendar::where(
            'code',
            'DEFAULT_CM'
        )->first();

        if (!$calendar) {
            return;
        }

        $days = [

            [
                'day_of_week' => 1,
                'day_name' => 'Lundi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 2,
                'day_name' => 'Mardi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 3,
                'day_name' => 'Mercredi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 4,
                'day_name' => 'Jeudi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 5,
                'day_name' => 'Vendredi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 6,
                'day_name' => 'Samedi',
                'is_working_day' => true,
                'counts_for_leave' => true,
            ],

            [
                'day_of_week' => 7,
                'day_name' => 'Dimanche',
                'is_working_day' => false,
                'counts_for_leave' => false,
            ],

        ];

        foreach ($days as $day) {

            WorkCalendarWorkingDay::updateOrCreate(

                [
                    'work_calendar_id' => $calendar->id,
                    'day_of_week' => $day['day_of_week'],
                ],

                [
                    'day_name' => $day['day_name'],
                    'is_working_day' => $day['is_working_day'],
                    'counts_for_leave' => $day['counts_for_leave'],
                    'working_ratio' => 1,
                ]

            );

        }
    }
}