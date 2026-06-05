<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {

            $table->id();

        

            /**
             * FEB associée
             */
            $table->foreignId('purchase_request_id')
                ->nullable();

            /**
             * Demandeur
             */
            $table->unsignedBigInteger('requester_id');

            /**
             * Directeur sélectionné
             */
            $table->unsignedBigInteger('director_id')
                ->nullable();

            /**
             * Urgente / Non urgente / Fonctionnement / Production
             */
            $table->enum('request_type', [
                'URGENT',
                'NON_URGENT',
                'OPERATING',
                'PRODUCTION'
            ]);

            /**
             * Date souhaitée d'exécution
             */
            $table->date('expected_execution_date')
                ->nullable();

            /**
             * Observations achat
             */
            $table->text('observation')
                ->nullable();

            /**
             * Décision service achat
             */
            $table->text('decision')
                ->nullable();

            /**
             * Statut métier
             */
            $table->enum('status', [
                'DRAFT',
                'PENDING_DIRECTOR_APPROVAL',
                'APPROVED',
                'REJECTED',
                'FUNDED',
                'PURCHASED',
                'REGULARIZED',
                'CLOSED'
            ])->default('DRAFT');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchases');
    }
}