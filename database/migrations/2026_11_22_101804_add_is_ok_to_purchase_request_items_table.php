<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsOkToPurchaseRequestItemsTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->boolean('is_ok')->default(false)->after('notice');
        });
    }

    public function down()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('is_ok');
        });
    }
}