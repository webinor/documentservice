<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTermsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();

            // Identifiant technique stable.
            $table->string('code', 50)->unique();

            // Libellé présenté à l'utilisateur.
            $table->string('name', 150);

            $table->text('description')->nullable();

            /*
             * IMMEDIATE : paiement immédiat.
             * NET_DAYS : paiement après un nombre de jours.
             * INSTALLMENTS : paiement en plusieurs échéances.
             */
            $table->enum('payment_mode', [
                'IMMEDIATE',
                'NET_DAYS',
                'INSTALLMENTS',
            ]);

            // Utilisé principalement avec NET_DAYS.
            $table->unsignedSmallInteger('due_days')->nullable();

            // Une seule condition peut être définie comme valeur par défaut
            // par les règles métier de l'application.
            $table->boolean('is_default')->default(false);

            // Une condition inactive n'est plus proposée pour les nouveaux BC.
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('payment_mode');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_terms');
    }
}