<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCaseNumberToRegularizationSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->string('case_number', 100)
                ->nullable()
                ->after('assistance_mode');
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
            $table->dropColumn('case_number');
        });
    }
}