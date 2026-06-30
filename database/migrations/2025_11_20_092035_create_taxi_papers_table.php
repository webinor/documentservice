<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxiPapersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxi_papers', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('document_id')
                  ->after('id')
                  ->constrained('documents')
                  ->cascadeOnDelete();
            
            $table->string('reason');
            $table->json('rides');//trajets
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
        Schema::dropIfExists('taxi_papers');
    }
}
