<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_references', function (Blueprint $table) {
           $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('document_reference_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reference_type_code');

            $table->string('reference');

            $table->string('attachment')->nullable();

            $table->json('metadata')->nullable();

            $table->foreignId('created_by')
                ->nullable();

            $table->timestamps();

            $table->index('document_id');

            $table->index('reference');

            $table->index([
                'document_id',
                'document_reference_type_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_references');
    }
}
