<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_folders', function (Blueprint $table) {
            
            $table->id();

            // 🔹 Clés étrangères
            $table->unsignedSmallInteger('department_id');
            $table->unsignedBigInteger('folder_id');

            // 🔹 Index et contraintes
            $table->foreign('folder_id')
                  ->references('id')
                  ->on('folders')
                  ->onDelete('cascade');

            // 🔹 Contrainte d’unicité pour éviter les doublons
            $table->unique(['department_id', 'folder_id']);

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
        Schema::dropIfExists('department_folders');
    }
}
