<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRegularizationItemsQuantitiesAndAmounts extends Migration
{
    public function up()
    {
         Schema::table('regularization_items', function (Blueprint $table) {
        $table->renameColumn('quantity', 'planned_quantity');
        // $table->renameColumn('total_amount', 'planned_amount');
    });

    Schema::table('regularization_items', function (Blueprint $table) {
        $table->unsignedMediumInteger('actual_quantity')
            ->nullable()
            ->after('planned_quantity');

        // $table->unsignedMediumInteger('actual_amount')
        //     ->nullable()
        //     ->after('planned_amount');
    });
    }

    public function down()
    {
         Schema::table('regularization_items', function (Blueprint $table) {
        $table->dropColumn([
            'actual_quantity',
            'actual_amount',
        ]);
    });

    Schema::table('regularization_items', function (Blueprint $table) {
        $table->renameColumn('planned_quantity', 'quantity');
        // $table->renameColumn('planned_amount', 'total_amount');
    });
    }
}