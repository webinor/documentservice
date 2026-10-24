<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAttachmentIdFromPurchaseSupplierQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->dropColumn('attachment_id');
        });
    }

    public function down()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('attachment_id')->nullable();
        });
    }
}