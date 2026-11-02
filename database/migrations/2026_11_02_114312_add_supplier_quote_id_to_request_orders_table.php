<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupplierQuoteIdToRequestOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_quote_id')
                // ->nullable()
                ->after('id');

            $table->index(
                'supplier_quote_id',
                'request_orders_supplier_quote_id_index'
            );
        });
    }

    public function down()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropIndex('request_orders_supplier_quote_id_index');
            $table->dropColumn('supplier_quote_id');
        });
    }
}