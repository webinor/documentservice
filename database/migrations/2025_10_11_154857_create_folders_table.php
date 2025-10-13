<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('notify_allowed_user')->default(false)
                  ->comment('Indique si les utilisateurs autorisés doivent être notifiés lors d’un ajout de document');
   
            $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade'); // si dossiers imbriqués
            $table->unsignedSmallInteger('created_by');//->nullable();
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
        Schema::dropIfExists('folders');
    }
}
