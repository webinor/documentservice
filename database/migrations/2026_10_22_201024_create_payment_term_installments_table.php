<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTermInstallmentsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_term_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_term_id')
                ->constrained('payment_terms')
                ->cascadeOnDelete();

            // Ordre de l'échéance : 1, 2, 3...
            $table->unsignedSmallInteger('sequence');

            $table->string('label', 150);

            /*
             * ORDER_DATE       : date de commande.
             * DELIVERY         : livraison.
             * INVOICE_RECEIVED : réception de la facture.
             * ACCEPTANCE       : réception conforme.
             * CUSTOM_DATE      : date définie manuellement.
             */
            $table->enum('trigger_type', [
                'ORDER_DATE',
                'DELIVERY',
                'INVOICE_RECEIVED',
                'ACCEPTANCE',
                'CUSTOM_DATE',
            ]);

            // Nombre de jours à ajouter à la date de référence.
            $table->unsignedSmallInteger('days_after_trigger')->default(0);

            /*
             * Pourcentage de la commande associé à cette échéance.
             * La somme des pourcentages d'une condition doit être 100.
             */
            $table->decimal('percentage', 5, 2);

            $table->timestamps();

            $table->unique(
                ['payment_term_id', 'sequence'],
                'payment_term_installments_term_sequence_unique'
            );

            $table->index('trigger_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_term_installments');
    }
}