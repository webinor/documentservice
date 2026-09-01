<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_calendar_rules', function (Blueprint $table) {

            $table->id();

            $table->foreignId('work_calendar_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Type de règle.
             *
             * ALTERNATE_WEEKLY :
             * un jour sur deux semaines.
             */
            $table->string('type');

            /*
             * 1 = lundi
             * 2 = mardi
             * ...
             * 6 = samedi
             * 7 = dimanche
             */
            $table->tinyInteger('day_of_week')
                ->nullable();

            /*
             * Date de référence du cycle.
             *
             * Exemple :
             * 2026-01-03
             *
             * Si cette date est un samedi travaillé,
             * les samedis suivants alternent.
             */
            $table->date('reference_date')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->json('settings')
                ->nullable();

            $table->timestamps();

            $table->index([
                'work_calendar_id',
                'day_of_week',
                'is_active'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_calendar_rules');
    }
};