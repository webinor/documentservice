<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToFeeNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_notes', function (Blueprint $table) {
            $table->string('reason');
            $table->unsignedMediumInteger('amount');
            $table->unsignedBigInteger('beneficiary')->after('id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_notes', function (Blueprint $table) {
            $table->dropColumn('reason','amount','beneficiary');
        });
    }
}
