<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseReceiptsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('request_order_id');

            $table->string('code', 50)->unique();

            $table->string('reference')->nullable();

            $table->boolean('physical_receipt_received')
                ->default(false);

            $table->timestamp('physical_receipt_received_at')
                ->nullable();

            $table->unsignedBigInteger(
                'physical_receipt_received_by_employee_id'
            )->nullable();

            $table->timestamps();

            $table->foreign('request_order_id')
                ->references('id')
                ->on('request_orders')
                ->onDelete('cascade');

            $table->index([
                'request_order_id',
                'physical_receipt_received'
            ], 'purchase_receipts_order_received_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipts');
    }
}