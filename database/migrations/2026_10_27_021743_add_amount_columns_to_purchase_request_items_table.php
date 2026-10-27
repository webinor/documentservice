<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmountColumnsToPurchaseRequestItemsTable extends Migration
{
    /**
     * Ajoute les montants demandés et approuvés
     * aux articles des demandes d'achat.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->decimal('requested_amount', 15, 2)
                ->nullable()
                ->after('requested_quantity');

            $table->decimal('approved_amount', 15, 2)
                ->nullable()
                ->after('approved_quantity');
        });
    }

    /**
     * Supprime les colonnes ajoutées.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn([
                'requested_amount',
                'approved_amount',
            ]);
        });
    }
}