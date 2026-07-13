<?php

namespace Database\Seeders;

use App\Models\PublicHoliday;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MovablePublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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

        /*
        |--------------------------------------------------------------------------
        | Date de Pâques
        |--------------------------------------------------------------------------
        */
        $easter = Carbon::instance(
            \DateTime::createFromFormat(
                'U',
                easter_date($year)
            )
        );

        $holidays = [

            /*
            |--------------------------------------------------------------------------
            | Vendredi Saint
            |--------------------------------------------------------------------------
            */
            [
                'date' => $easter->copy()->subDays(2),
                'name' => 'Vendredi Saint',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lundi de Pâques
            |--------------------------------------------------------------------------
            */
            [
                'date' => $easter->copy()->addDay(),
                'name' => 'Lundi de Pâques',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ascension
            |--------------------------------------------------------------------------
            */
            [
                'date' => $easter->copy()->addDays(39),
                'name' => 'Ascension',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pentecôte
            |--------------------------------------------------------------------------
            */
            [
                'date' => $easter->copy()->addDays(49),
                'name' => 'Pentecôte',
            ],

            /*
            |--------------------------------------------------------------------------
            | Lundi de Pentecôte
            |--------------------------------------------------------------------------
            */
            [
                'date' => $easter->copy()->addDays(50),
                'name' => 'Lundi de Pentecôte',
            ],

        ];

        foreach ($holidays as $holiday) {

            PublicHoliday::updateOrCreate(

                [
                    'work_calendar_id' => $calendar->id,
                    'date' => $holiday['date']->format('Y-m-d'),
                ],

                [
                    'name' => $holiday['name'],
                    'counts_for_leave' => false,
                    'is_recurring' => false,
                ]

            );

        }
    }
}