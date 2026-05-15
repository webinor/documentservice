<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_type_statuses', function (Blueprint $table) {

            $table->id();

            /**
             * Type de document
             */
            $table->foreignId('document_type_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Status autorisé
             */
            $table->foreignId('document_status_id')
                ->constrained()
                ->cascadeOnDelete();

            /**
             * Status par défaut pour ce type ?
             */
            $table->boolean('is_default')->default(false);

            /**
             * Ordre d'affichage optionnel
             */
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            /**
             * Un status ne doit pas être dupliqué
             * pour un même type de document
             */
            $table->unique([
                'document_type_id',
                'document_status_id'
            ], 'doc_type_status_unique');

            /**
             * Index utiles
             */
            $table->index('is_default');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_type_statuses');
    }
};