<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentStatusHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->enum('old_status', ['EN_ATTENTE', 'BLOQUE', 'VALIDE', 'REJETE'])->nullable();
            $table->enum('new_status', ['EN_ATTENTE', 'BLOQUE', 'VALIDE', 'REJETE']);
            $table->unsignedBigInteger('change_by');
            $table->dateTime('change_at')->useCurrent();
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
        Schema::dropIfExists('document_status_histories');
    }
}
