<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegularizationItemReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
                Schema::create('regularization_item_receipt', function (Blueprint $table) {
    $table->id();

    $table->foreignId('regularization_item_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('regularization_receipt_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->integer('allocated_amount')
        ->nullable();

    $table->timestamps();


    $table->unique(
    ['regularization_item_id', 'regularization_receipt_id'],
    'reg_item_receipt_unique'
);
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regularization_item_receipts');
    }
}
