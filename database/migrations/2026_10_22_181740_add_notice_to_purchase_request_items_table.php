<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoticeToPurchaseRequestItemsTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->text('notice')->nullable();
        });
    }

    public function down()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('notice');
        });
    }
}