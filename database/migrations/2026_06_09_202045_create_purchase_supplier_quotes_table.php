<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseSupplierQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_supplier_quotes', function (Blueprint $table) {

            $table->id();

            /**
             * FEB concernée
             */
            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnDelete();

            /**
             * Fournisseur
             * (référence vers ton service fournisseur si tu en as un)
             */
            $table->unsignedBigInteger('supplier_id')->nullable();

            /**
             * Informations devis
             */
            $table->string('reference')->nullable();
            $table->decimal('amount', 18, 2)->default(0);

            /**
             * Observations achats
             */
            $table->text('comment')->nullable();

            /**
             * Pièce jointe du devis
             */
            $table->unsignedBigInteger('attachment_id')->nullable();

            /**
             * Fournisseur retenu ?
             */
            $table->boolean('is_selected')
                ->default(false);

            /**
             * Statut du devis
             */
            $table->enum('status', [
                'PENDING',
                'REJECTED',
                'SELECTED'
            ])->default('PENDING');

            /**
             * Traçabilité
             */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            /**
             * Index
             */
            $table->index('purchase_request_id');
            $table->index('supplier_id');
            $table->index('is_selected');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_supplier_quotes');
    }
}