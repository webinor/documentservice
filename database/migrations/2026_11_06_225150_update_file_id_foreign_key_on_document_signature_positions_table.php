<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFileIdForeignKeyOnDocumentSignaturePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Remplace nullOnDelete par cascadeOnDelete.
     */
    public function up()
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            // Supprime l'ancienne contrainte.
            $table->dropForeign(['file_id']);

            // Ajoute une nouvelle contrainte avec suppression en cascade.
            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->dropForeign(['file_id']);

            // Restaure le comportement précédent.
            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('set null');
        });
    }
}