<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicHoliday;
use App\Models\WorkCalendar;

class PublicHolidaySeeder extends Seeder
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

        $year = now()->year;

        $holidays = [

            [
                'date' => "$year-01-01",
                'name' => "Nouvel An",
            ],

            [
                'date' => "$year-02-11",
                'name' => "Fête de la Jeunesse",
            ],

            [
                'date' => "$year-05-01",
                'name' => "Fête du Travail",
            ],

            [
                'date' => "$year-05-20",
                'name' => "Fête Nationale",
            ],

            [
                'date' => "$year-08-15",
                'name' => "Assomption",
            ],

            [
                'date' => "$year-12-25",
                'name' => "Noël",
            ],

        ];

        foreach ($holidays as $holiday) {

            PublicHoliday::updateOrCreate(

                [
                    'work_calendar_id' => $calendar->id,
                    'date' => $holiday['date'],
                ],

                [
                    'name' => $holiday['name'],
                    'counts_for_leave' => false,
                    'is_recurring' => true,
                ]

            );

        }
    }
}