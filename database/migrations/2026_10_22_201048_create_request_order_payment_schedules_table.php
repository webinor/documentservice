<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestOrderPaymentSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('request_order_payment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('request_order_id')
                ->constrained('request_orders')
                ->cascadeOnDelete();

            // Référence facultative vers la ligne du catalogue.
            // La copie des informations ci-dessous reste la référence historique.
            $table->unsignedBigInteger('payment_term_installment_id')->nullable();

            $table->unsignedSmallInteger('sequence');

            $table->string('label', 150);

            $table->string('trigger_type', 30);

            $table->unsignedSmallInteger('days_after_trigger')->default(0);

            $table->decimal('percentage', 5, 2);

            // Montant calculé lors de la création de l'échéancier.
            $table->decimal('amount', 18, 2);

            // Date effective prévue, à calculer lorsque le déclencheur est connu.
            $table->date('due_date')->nullable();

            $table->enum('status', [
                'PLANNED',
                'DUE',
                'PARTIALLY_PAID',
                'PAID',
                'CANCELLED',
            ])->default('PLANNED');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['request_order_id', 'sequence'],
                'request_order_payment_schedules_order_sequence_unique'
            );

            $table->index('due_date');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_order_payment_schedules');
    }
}