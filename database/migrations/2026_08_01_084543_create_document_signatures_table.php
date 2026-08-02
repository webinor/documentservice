<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('document_signatures', function (Blueprint $table) {

            $table->id();

            /**
             * Document signé
             */
            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Type de signature
             *
             * Exemple :
             * RECEIPT
             * SUPPLIER_INVOICE
             */
            $table->string('signature_type', 100);


            /**
             * Utilisateur ayant signé
             *
             * User service externe
             */
            $table->unsignedBigInteger('signed_by')
                ->nullable();


            /**
             * Informations signature
             *
             * Peut être :
             * - fichier signature
             * - hash
             * - certificat
             */
            $table->string('signature_file')
                ->nullable();



            /**
             * Date de signature
             */
            $table->timestamp('signed_at')
                ->nullable();



            /**
             * Métadonnées éventuelles
             */
            $table->json('metadata')
                ->nullable();



            $table->timestamps();



            /**
             * Une seule signature par type
             * pour un document
             */
            $table->unique(
                [
                    'document_id',
                    'signature_type'
                ],
                'doc_signature_unique'
            );

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};