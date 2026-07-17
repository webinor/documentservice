<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegularizationItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regularization_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('regularization_sheet_id')
                ->constrained()
                ->cascadeOnDelete();

            // Désignation de l'article
            $table->string('designation')->nullable();

            // Quantité
            $table->unsignedMediumInteger('quantity')
                ->nullable();

            // Prix unitaire
            $table->unsignedMediumInteger('unit_price')
                ->nullable();

            // Total de la ligne (quantity × unit_price)
            $table->unsignedMediumInteger('total_amount')
                ->nullable();

            // Justificatif éventuel
            $table->string('receipt')->nullable();

            // Observation
            $table->text('comment')->nullable();

            // Ordre d'affichage
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index('regularization_sheet_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regularization_items');
    }
}