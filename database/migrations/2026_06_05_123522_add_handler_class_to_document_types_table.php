<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHandlerClassToDocumentTypesTable extends Migration
{
    public function up()
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('handler_class')
                ->nullable()
                ->after('class_name');
        });
    }

    public function down()
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('handler_class');
        });
    }
}