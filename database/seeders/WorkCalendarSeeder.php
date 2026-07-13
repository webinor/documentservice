<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkCalendar;

class WorkCalendarSeeder extends Seeder
{
    public function run()
    {
        WorkCalendar::updateOrCreate(

            [
                'code' => 'DEFAULT_CM'
            ],

            [
                'name' => 'Calendrier Cameroun',
                'organization_id' => null,
                'is_default' => true,
                'description' => 'Calendrier de travail par défaut.',
            ]

        );
    }
}