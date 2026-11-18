<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseReceiptPaymentScheduleTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_receipt_payment_schedule', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_receipt_id');

            $table->unsignedBigInteger('request_order_payment_schedule_id');

            $table->unsignedBigInteger('allocated_amount')->default(0);

            $table->timestamps();

           $table->foreign('purchase_receipt_id', 'prps_receipt_fk')
    ->references('id')
    ->on('purchase_receipts')
    ->onDelete('cascade');

$table->foreign(
    'request_order_payment_schedule_id',
    'prps_schedule_fk'
)
    ->references('id')
    ->on('request_order_payment_schedules')
    ->onDelete('cascade');

           $table->unique(
    [
        'purchase_receipt_id',
        'request_order_payment_schedule_id',
    ],
    'prps_receipt_schedule_unique'
);
        
            });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_receipt_payment_schedule');
    }
}