<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseSettlementsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_settlements', function (Blueprint $table) {

            $table->id();

            // /**
            //  * Document GED
            //  */
            // $table->foreignId('document_id')
            //     ->constrained()
            //     ->cascadeOnDelete();

            /**
             * DA associée
             */
            $table->foreignId('purchase_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Montants
             */
            $table->decimal('amount_requested', 15, 2)
                ->default(0);

            $table->decimal('amount_approved', 15, 2)
                ->default(0);

            $table->decimal('amount_paid', 15, 2)
                ->default(0);

            /**
             * Justificatifs déposés
             */
            $table->decimal('amount_justified', 15, 2)
                ->default(0);

            /**
             * Solde calculé
             */
            $table->decimal('balance', 15, 2)
                ->default(0);

            /**
             * Paiement
             */
            $table->enum('payment_mode', [
                'CASH',
                'TRANSFER',
                'CHECK'
            ])->nullable();

            $table->string('payment_reference')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            /**
             * Workflow métier
             */
            $table->enum('status', [
                'DRAFT',
                'WAITING_PAYMENT',
                'PAID',
                'WAITING_SETTLEMENT',
                'PARTIALLY_SETTLED',
                'SETTLED',
                'CLOSED'
            ])->default('DRAFT');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_settlements');
    }
}