<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActualAmountToRegularizationSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->decimal('actual_amount', 15, 2)
                  ->default(0)
                  ->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->dropColumn('actual_amount');
        });
    }
}