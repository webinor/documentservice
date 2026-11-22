<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mission_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code')->unique();

            $table->unsignedBigInteger('mission_id');

            $table->string('name')->nullable();
            $table->string('reference')->nullable();
            $table->date('receipt_date')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('mission_id');

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mission_receipts');
    }
};