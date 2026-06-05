<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRequestItemsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('purchase_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('designation');

            $table->integer('requested_quantity');

            /**
             * Quantité validée après validation partielle
             */
            $table->integer('approved_quantity')
                ->nullable();

            $table->text('specification')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_request_items');
    }
}