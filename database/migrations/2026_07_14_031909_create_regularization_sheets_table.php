<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegularizationSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regularization_sheets', function (Blueprint $table) {
            $table->id();
                $table->foreignId('document_id')
                  ->constrained('documents')
                  ->cascadeOnDelete();
            $table->string('reason');
            $table->unsignedMediumInteger('amount');
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
        Schema::dropIfExists('regularization_sheets');
    }
}
