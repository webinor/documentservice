<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceProviderToFeeNote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fee_notes', function (Blueprint $table) {
            $table->foreignId('invoice_provider_id')->constrained('invoice_providers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fee_notes', function (Blueprint $table) {
              // ⚠️ Supprimer d’abord la contrainte avant la colonne
            $table->dropForeign(['invoice_provider_id']);
            $table->dropColumn('invoice_provider_id');
        });
    }
}
