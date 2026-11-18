<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceivedFieldsToRequestOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::table('request_order_items', function (Blueprint $table) {

            $table->unsignedInteger('received_quantity')
                ->default(0)
                ->after('quantity');

            $table->decimal('actual_unit_price', 15, 2)
                ->default(0)
                ->after('unit_price');

            $table->decimal('total_received_amount', 15, 2)
                ->default(0)
                ->after('actual_unit_price');

        });
    }

    public function down()
    {
        Schema::table('request_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'received_quantity',
                'actual_unit_price',
                'total_received_amount'
            ]);
        });
    }
}