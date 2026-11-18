<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// class CreatePurchaseReceiptRequestOrderItemTable extends Migration
return new class extends Migration
{
    public function up()
    {
        Schema::create(
    'purchase_receipt_request_order_items',
    function (Blueprint $table) {
        $table->bigIncrements('id');

        $table->unsignedBigInteger('purchase_receipt_id');
        $table->unsignedBigInteger('request_order_item_id');

        $table->unsignedInteger('received_quantity')->default(0);

        $table->decimal('received_unit_price', 15, 2)
            ->nullable();

        $table->decimal('allocated_amount', 15, 2)
            ->default(0);

        $table->timestamps();

        $table->foreign('purchase_receipt_id','pr_receipt_item_receipt_fk')
            ->references('id')
            ->on('purchase_receipts')
            ->onDelete('cascade');

        $table->foreign('request_order_item_id','pr_receipt_item_item_fk')
            ->references('id')
            ->on('request_order_items')
            ->onDelete('cascade');

        $table->unique(
            ['purchase_receipt_id', 'request_order_item_id'],
            'purchase_receipt_item_unique'
        );
    }
);
    }

    public function down()
    {
        Schema::dropIfExists(
            'purchase_receipt_request_order_items'
        );
    }
};