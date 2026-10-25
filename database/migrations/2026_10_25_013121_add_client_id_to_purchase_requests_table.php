<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdToPurchaseRequestsTable extends Migration
{
    /**
     * Ajoute la référence au client sur les demandes d'achat.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')
                ->nullable()
                ->after('id')
                ->index();
        });
    }

    /**
     * Supprime la colonne en cas de rollback.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
        });
    }
}