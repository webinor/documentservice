<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileSignaturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("file_signatures", function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId("file_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger("user_id");

            /*
    |--------------------------------------------------------------------------
    | Position dans le document
    |--------------------------------------------------------------------------
    */

            $table->integer("page")->default(1);

            $table->float("x");

            $table->float("y");

            $table->float("width");

            $table->float("height");

            /*
    |--------------------------------------------------------------------------
    | Fichier généré
    |--------------------------------------------------------------------------
    */

            $table->string("signed_path")->nullable();

            $table->timestamp("signed_at")->nullable();

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
        Schema::dropIfExists("file_signatures");
    }
}
