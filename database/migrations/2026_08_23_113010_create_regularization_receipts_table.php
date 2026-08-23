<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegularizationReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('regularization_receipts', function (Blueprint $table) {
            $table->id();

            $table->string('code');


            $table->foreignId('regularization_sheet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reference')->nullable();
            $table->date('receipt_date')->nullable();
            $table->string('description')->nullable();

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
        Schema::dropIfExists('regularization_receipts');
    }
}
