<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseSupplierQuotePaymentInstallmentsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_supplier_quote_payment_installments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('purchase_supplier_quote_id');

            $table->unsignedInteger('sequence');

            $table->string('label', 150);

            $table->string('trigger_type', 50);

            $table->unsignedSmallInteger('days_after_trigger')->default(0);

            $table->decimal('percentage', 5, 2);

            $table->timestamps();

            $table->foreign('purchase_supplier_quote_id','psq_installments_quote_fk')
                ->references('id')
                ->on('purchase_supplier_quotes')
                ->onDelete('cascade');

            $table->unique(
                ['purchase_supplier_quote_id', 'sequence'],
                'quote_payment_installments_sequence_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_supplier_quote_payment_installments');
    }
}