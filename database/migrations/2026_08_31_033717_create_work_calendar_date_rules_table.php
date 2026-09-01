<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarDateRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_calendar_date_rules', function (Blueprint $table) {

    $table->id();

    $table->foreignId('work_calendar_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->date('date');

    $table->boolean('is_working_day');

    $table->boolean('counts_for_leave');

    $table->decimal('working_ratio', 3, 2)
        ->default(1);

    $table->string('reason')->nullable();

    $table->timestamps();

    $table->unique([
        'work_calendar_id',
        'date'
    ]);
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_calendar_date_rules');
    }
}
