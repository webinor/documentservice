<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_requests', function (Blueprint $table) {

            $table->id();

            /**
             * Document GED
             */
            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Demandeur
             */
            $table->unsignedBigInteger('requested_by');

            /**
             * Service demandeur
             */
            $table->unsignedBigInteger('requester_service_id')
                ->nullable();

            /**
             * Service destinataire
             */
            $table->unsignedBigInteger('destination_service_id')
                ->nullable();

            /**
             * Objet du besoin
             */
            // $table->string('title');

            $table->text('description')
                ->nullable();

            /**
             * Existe déjà ?
             */
            $table->boolean('already_exists')
                ->default(false);

            /**
             * Pourquoi un nouvel achat ?
             */
            $table->text('renewal_reason')
                ->nullable();

            /**
             * Statut métier
             */
            $table->enum('status', [
                'DRAFT',
                'SUBMITTED',
                'APPROVED',
                'PARTIALLY_APPROVED',
                'REJECTED'
            ])->default('DRAFT');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_requests');
    }
}