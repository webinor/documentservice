<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePublicHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('public_holidays', function (Blueprint $table) {

            $table->id();

            $table->foreignId('work_calendar_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date');

            $table->string('name');

            $table->boolean('counts_for_leave')
                ->default(false);

            $table->boolean('is_recurring')
                ->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique([
                'work_calendar_id',
                'date'
            ]);

        });
    }

    public function down()
    {
        Schema::dropIfExists('public_holidays');
    }
}