<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarWorkingDaysTable extends Migration
{
    public function up()
    {
        Schema::create('work_calendar_working_days', function (Blueprint $table) {

            $table->id();

            $table->foreignId('work_calendar_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            1=Lundi
            2=Mardi
            ...
            7=Dimanche
            */

            $table->tinyInteger('day_of_week');

            $table->string('day_name');

            // Jour travaillé ?
            $table->boolean('is_working_day')->default(true);

            /*
             * Peut être imputé sur un congé ?
             * Exemple :
             * dimanche = false
             */

            $table->boolean('counts_for_leave')->default(true);

            /*
             * Optionnel :
             * journée complète / demi-journée
             */

            $table->decimal('working_ratio',3,2)
                ->default(1);

            $table->timestamps();

            $table->unique([
                'work_calendar_id',
                'day_of_week'
            ]);

        });
    }

    public function down()
    {
        Schema::dropIfExists(
            'work_calendar_working_days'
        );
    }
}