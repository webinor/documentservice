<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlannedAndActualAmountToRegularizationItemsTable extends Migration
{
    public function up()
    {
        Schema::table('regularization_items', function (Blueprint $table) {
            $table->unsignedMediumInteger('planned_amount')
                ->nullable()
                ->after('unit_price');

            $table->unsignedMediumInteger('actual_amount')
                ->nullable()
                ->after('planned_amount');
        });
    }

    public function down()
    {
        Schema::table('regularization_items', function (Blueprint $table) {
            $table->dropColumn([
                'planned_amount',
                'actual_amount',
            ]);
        });
    }
}