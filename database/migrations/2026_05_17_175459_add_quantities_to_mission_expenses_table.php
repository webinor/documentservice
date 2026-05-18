<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantitiesToMissionExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('mission_expenses', function (Blueprint $table) {

        $table->decimal(
            'planned_quantity',
            10,
            2
        )
        ->after('quantity')
        ->nullable();

        $table->decimal(
            'final_quantity',
            10,
            2
        )
        ->after('planned_quantity')
        ->nullable();

    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mission_expenses', function (Blueprint $table) {
            //
        });
    }
}
