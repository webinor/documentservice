<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('request_order_items', function (Blueprint $table) {
            $table->id();

            // Bon de commande auquel appartient la ligne.
            $table->unsignedBigInteger('request_order_id');

            // Article de la demande d'achat initiale, si applicable.
            $table->unsignedBigInteger('purchase_request_item_id')
                ->nullable();

            // Informations de l'article commandé.
            $table->string('designation');
            $table->text('description')->nullable();
            $table->text('specification')->nullable();
            $table->string('unit', 50)->nullable();

            // Quantité effectivement commandée.
            $table->decimal('quantity', 12, 3)->default(1);

            // Prix et calculs financiers.
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            // Ordre d'affichage de la ligne.
            $table->unsignedInteger('position')->default(1);

            $table->timestamps();

            // Suppression des lignes si le bon est supprimé.
            $table->foreign('request_order_id')
                ->references('id')
                ->on('request_orders')
                ->onDelete('cascade');

            // La demande initiale peut ne pas être disponible.
            $table->foreign('purchase_request_item_id')
                ->references('id')
                ->on('purchase_request_items')
                ->onDelete('set null');

            $table->index('request_order_id');
            $table->index('purchase_request_item_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_order_items');
    }
}