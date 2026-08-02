<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeAmountNullableInRegularizationSheetsTable extends Migration
{
    public function up()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->unsignedInteger('amount')
                ->nullable()
                ->change();
        });
    }

    public function down()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->unsignedInteger('amount')
                ->nullable(false)
                ->change();
        });
    }
}