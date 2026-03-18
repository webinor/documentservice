<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {
            // Statut lisible pour l'utilisateur final
            $table->string('status')->nullable()->after('document_type_id');

            // Date d'échéance pour suivi paiement
            $table->date('date_due')->nullable()->after('status');

            // Montant déjà présent dans la relation invoice_provider, mais pour faciliter l'export
            $table->decimal('amount', 15, 2)->nullable()->after('date_due');

            // Nom du prestataire pour export rapide (optionnel si tu veux éviter les relations imbriquées)
            $table->string('prestataire_name')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'date_due',
                'amount',
                'prestataire_name',
            ]);
        });
    }
};