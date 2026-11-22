<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// class CreateMissionExpenseReceiptTable extends Migration
return new class extends Migration
{
    public function up()
    {
        Schema::create('mission_expense_receipt', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('mission_expense_id');
            $table->unsignedBigInteger('mission_receipt_id');

            $table->decimal('allocated_amount', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(
                ['mission_expense_id', 'mission_receipt_id'],
                'mission_expense_receipt_unique'
            );

            $table->index('mission_receipt_id');

            $table->foreign('mission_expense_id')
                ->references('id')
                ->on('mission_expenses')
                ->onDelete('cascade');

            $table->foreign('mission_receipt_id')
                ->references('id')
                ->on('mission_receipts')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mission_expense_receipt');
    }
};