<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityToMissionExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mission_expenses', function (Blueprint $table) {

            $table->integer('quantity')
                  ->nullable()
                  ->after('expense_category_id');

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

            $table->dropColumn('quantity');

        });
    }
}