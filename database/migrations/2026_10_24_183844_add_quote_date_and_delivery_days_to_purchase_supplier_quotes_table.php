<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteDateAndDeliveryDaysToPurchaseSupplierQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            // Date d'émission du devis fournisseur
            $table->date('quote_date')->nullable();

            // Délai de livraison estimé en jours
            $table->unsignedSmallInteger('delivery_days')->nullable();
        });
    }

    public function down()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'quote_date',
                'delivery_days',
            ]);
        });
    }
}