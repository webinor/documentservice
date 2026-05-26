<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGenerationModeToAttachmentTypesTable extends Migration
{
    public function up()
    {
        Schema::table('attachment_types', function (Blueprint $table) {

            $table->string('generation_mode')
                ->default('USER')//#SYSTEM
                ->comment('USER | SYSTEM')
                ->after('name');
        });
    }

    public function down()
    {
        Schema::table('attachment_types', function (Blueprint $table) {

            $table->dropColumn('generation_mode');
        });
    }
}