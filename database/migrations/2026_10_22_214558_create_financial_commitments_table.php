<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialCommitmentsTable extends Migration
{
    public function up()
    {
        Schema::create('financial_commitments', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * Document GED principal de l'engagement :
             * bon de commande, contrat, convention, etc.
             *
             * Le document appartient au même service.
             * La clé étrangère garantit son intégrité référentielle.
             *
             * Nullable : un engagement peut être créé sans document
             * GED associé dans un premier temps.
             */
            $table->unsignedBigInteger('document_id')
                ->nullable()
                ->index();

            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('set null');

            /*
             * Demande d'achat à l'origine de l'engagement.
             * Nullable pour les contrats ne provenant pas
             * d'une demande d'achat.
             */
            $table->unsignedBigInteger('purchase_request_id')
                ->nullable()
                ->index();

            $table->string('commitment_number', 100)
                ->nullable()
                ->unique();

            /*
             * Exemples :
             * PURCHASE_ORDER, SERVICE_CONTRACT, AGREEMENT.
             *
             * VARCHAR permet d'ajouter de nouveaux types
             * sans modifier une colonne ENUM.
             */
            $table->string('commitment_type', 50);

            $table->unsignedBigInteger('supplier_id')
                ->nullable()
                ->index();

            $table->string('supplier_name')->nullable();
            $table->string('title')->nullable();

            $table->decimal('initial_amount', 18, 2)->default(0);
            $table->decimal('approved_amount', 18, 2)->nullable();

            $table->string('currency', 3)->default('XAF');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('status', 30)
                ->default('DRAFT')
                ->index();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::table('financial_commitments', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });

        Schema::dropIfExists('financial_commitments');
    }
}