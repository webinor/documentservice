<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeCategoryIdToExpenseLimitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expense_limits', function (Blueprint $table) {

            $table->unsignedBigInteger('employee_category_id')
                ->nullable()
                ->after('expense_category_id');

            // si catégories locales synchronisées
            // $table->foreign('employee_category_id')
            //     ->references('id')
            //     ->on('employee_categories')
            //     ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expense_limits', function (Blueprint $table) {

            // $table->dropForeign(['employee_category_id']);

            $table->dropColumn('employee_category_id');

        });
    }
}