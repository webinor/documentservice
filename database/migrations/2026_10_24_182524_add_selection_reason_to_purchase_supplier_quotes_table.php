<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelectionReasonToPurchaseSupplierQuotesTable extends Migration
{
    /**
     * Ajoute le motif de sélection du devis.
     */
    public function up()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->text('selection_reason')
                ->nullable()
                ->after('is_selected');
        });
    }

    /**
     * Supprime la colonne en cas de rollback.
     */
    public function down()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->dropColumn('selection_reason');
        });
    }
}