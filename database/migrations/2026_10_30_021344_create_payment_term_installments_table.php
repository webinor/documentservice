<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTermInstallmentsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_term_installments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('payment_term_id');

            $table->unsignedInteger('sequence');

            $table->string('label', 150);

            /*
             * ORDER_APPROVAL
             * ORDER_SIGNATURE
             * ADVANCE_PAYMENT
             * DELIVERY
             * PARTIAL_DELIVERY
             * RECEPTION
             * INSTALLATION
             * SERVICE_COMPLETION
             * ACCEPTANCE
             * INVOICE_RECEIPT
             * FIXED_DELAY
             */
            $table->string('trigger_type', 50);

            $table->unsignedSmallInteger('days_after_trigger')->default(0);

            $table->decimal('percentage', 5, 2);

            $table->timestamps();

            $table->foreign('payment_term_id')
                ->references('id')
                ->on('payment_terms')
                ->onDelete('cascade');

            $table->unique(
                ['payment_term_id', 'sequence'],
                'payment_term_installments_sequence_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_term_installments');
    }
}