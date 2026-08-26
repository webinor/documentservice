<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_signature_positions', function (Blueprint $table) {

            $table->id();


            /**
             * Document concerné
             */
            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

    //         $table->foreignId('file_id')
    // ->nullable()
    // ->constrained('files');
    // ->nullOnDelete();


            /**
             * Type de signature
             *
             * Exemple :
             * RECEIPT
             * SUPPLIER_INVOICE
             * CONTRACT
             */
            $table->string('signature_type', 100);



            /**
             * Position dans le PDF
             */
            $table->unsignedInteger('page_number')
                ->default(1);


            /**
             * Coordonnées du rectangle
             */
            $table->decimal('x', 10, 2);

            $table->decimal('y', 10, 2);


            /**
             * Taille de la zone de signature
             */
            $table->decimal('width', 10, 2)
                ->default(150);

            $table->decimal('height', 10, 2)
                ->default(50);



            $table->timestamps();



            /**
             * Un document ne peut avoir
             * qu'une position par type de signature
             */
            $table->unique(
                [
                    'document_id',
                    'signature_type'
                ],
                'doc_signature_position_unique'
            );

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('document_signature_positions');
    }
};