<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_verifications', function (Blueprint $table) {
             $table->id();

    $table->unsignedBigInteger('document_id');

    $table->string('verification_code')
        ->unique();

    $table->string('version')
        ->default('1.0');

        $table->json('snapshot')->nullable();

$table->string('pdf_hash')->nullable();

    $table->boolean('is_valid')
        ->default(true);

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
        Schema::dropIfExists('document_verifications');
    }
}
