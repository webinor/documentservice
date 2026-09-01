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
        Schema::table('document_signatures', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Ajouter le nouvel index
            |--------------------------------------------------------------------------
            |
            | Nouvelle règle :
            |
            | UNIQUE(document_id, signature_type, signed_by)
            |
            */

            $table->unique(
                [
                    'document_id',
                    'signature_type',
                    'signed_by',
                ],
                'doc_signature_user_unique'
            );

                        /*
            |--------------------------------------------------------------------------
            | Supprimer l'ancien index
            |--------------------------------------------------------------------------
            |
            | Ancienne règle :
            |
            | UNIQUE(document_id, signature_type)
            |
            */

            $table->dropUnique('doc_signature_unique');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {

                    /*
            |--------------------------------------------------------------------------
            | Restaurer l'ancien index
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'document_id',
                    'signature_type',
                ],
                'doc_signature_unique'
            );


            /*
            |--------------------------------------------------------------------------
            | Supprimer le nouvel index
            |--------------------------------------------------------------------------
            */

            $table->dropUnique('doc_signature_user_unique');

        });
    }
};