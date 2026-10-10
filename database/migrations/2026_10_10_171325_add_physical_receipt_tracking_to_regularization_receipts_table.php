<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhysicalReceiptTrackingToRegularizationReceiptsTable extends Migration
{
    public function up()
    {
        Schema::table('regularization_receipts', function (Blueprint $table) {

            $table->boolean('physical_receipt_received')
                ->nullable()
                ->default(null);

            $table->timestamp('physical_receipt_received_at')
                ->nullable();

            $table->unsignedBigInteger('physical_receipt_received_by')
                ->nullable();

        });
    }

    public function down()
    {
        Schema::table('regularization_receipts', function (Blueprint $table) {

            $table->dropColumn([
                'physical_receipt_received',
                'physical_receipt_received_at',
                'physical_receipt_received_by',
            ]);

        });
    }
}