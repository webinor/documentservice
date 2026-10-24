<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRequestOrdersAddPurchaseOrderFields extends Migration
{
    public function up()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            // Demande d'achat à l'origine du bon de commande.
            $table->unsignedBigInteger('purchase_request_id')
                ->nullable()
                ->after('document_id');

            // Identification du bon de commande.
            $table->string('order_number', 100)
                ->nullable()
                ->unique();

            // Référence au fournisseur géré par le service fournisseur.
            $table->unsignedBigInteger('supplier_id')->nullable();

            // Instantané du nom du fournisseur au moment de l'émission.
            $table->string('supplier_name')->nullable();

            $table->string('supplier_reference')->nullable();

            // Dates.
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();

            // Montants financiers.
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('shipping_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);

            // Cycle de vie du bon de commande.
            $table->string('status', 30)->default('DRAFT');

            // Informations complémentaires.
            $table->text('notes')->nullable();

            // Traçabilité.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('purchase_request_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('order_date');
        });

        Schema::table('request_orders', function (Blueprint $table) {
            $table->foreign('purchase_request_id')
                ->references('id')
                ->on('purchase_requests')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_id']);

            $table->dropUnique(['order_number']);

            $table->dropIndex(['purchase_request_id']);
            $table->dropIndex(['supplier_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['order_date']);

            $table->dropColumn([
                'purchase_request_id',
                'order_number',
                'supplier_id',
                'supplier_name',
                'supplier_reference',
                'order_date',
                'expected_delivery_date',
                'subtotal',
                'tax_amount',
                'discount_amount',
                'shipping_amount',
                'total_amount',
                'status',
                'notes',
                'created_by',
                'updated_by',
            ]);
        });
    }
}