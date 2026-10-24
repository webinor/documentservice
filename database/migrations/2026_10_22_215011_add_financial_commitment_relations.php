<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinancialCommitmentRelations extends Migration
{
    public function up()
    {
        /*
         * Relation entre le bon de commande et l'engagement financier.
         *
         * On suppose ici que request_orders contient déjà :
         * id, document_id, created_at, updated_at.
         */
        Schema::table('request_orders', function (Blueprint $table) {
          

            $table->unsignedBigInteger('financial_commitment_id')
                ->nullable()
                ->index();
        });

        /*
         * Une facture fournisseur peut être rattachée à un engagement.
         * Le champ reste nullable pour ne pas bloquer les anciennes
         * factures qui n'ont pas encore été associées à un engagement.
         */
        Schema::table('invoice_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('financial_commitment_id')
                ->nullable()
                ->index();
        });
    }

    public function down()
    {
        Schema::table('invoice_providers', function (Blueprint $table) {
            $table->dropColumn('financial_commitment_id');
        });

        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropColumn('financial_commitment_id');
        });
    }
}