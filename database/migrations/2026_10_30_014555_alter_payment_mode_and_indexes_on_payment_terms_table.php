<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AlterPaymentModeAndIndexesOnPaymentTermsTable extends Migration
{
    public function up()
    {
        /*
         * Conversion de payment_mode :
         * ENUM -> VARCHAR(50).
         *
         * Cela permet d'ajouter librement de nouveaux modes
         * de paiement sans modifier la structure SQL.
         */
        DB::statement("
            ALTER TABLE payment_terms
            MODIFY payment_mode VARCHAR(50) NOT NULL
        ");

        /*
         * Suppression des anciens index.
         */
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropIndex(['payment_mode']);
            $table->dropIndex(['is_active']);
        });

        /*
         * Création du nouvel index composite.
         */
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->index(
                ['is_active', 'payment_mode'],
                'payment_terms_active_mode_index'
            );
        });
    }

    public function down()
    {
        /*
         * Attention :
         * la conversion inverse vers ENUM suppose que toutes
         * les valeurs présentes appartiennent à cette liste.
         */

        DB::statement("
            ALTER TABLE payment_terms
            MODIFY payment_mode ENUM(
                'IMMEDIATE',
                'NET_DAYS',
                'INSTALLMENTS'
            ) NOT NULL
        ");

        /*
         * Suppression de l'index composite.
         */
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropIndex('payment_terms_active_mode_index');
        });

        /*
         * Restauration des anciens index.
         */
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->index('payment_mode');
            $table->index('is_active');
        });
    }
}