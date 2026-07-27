<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentReferenceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_reference_types', function (Blueprint $table) {
             $table->id();

            $table->string('code')->unique();

            $table->string('label');

            $table->text('description')->nullable();

            // Le type nécessite-t-il un fichier ?
            $table->boolean('has_attachment')->default(false);

            // Plusieurs références de ce type autorisées ?
            $table->boolean('allow_multiple')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_reference_types');
    }
}
