<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViewOwnRouteToDocumentTypesTable extends Migration
{
    public function up()
    {
        Schema::table('document_types', function (Blueprint $table) {

            $table
                ->string('view_own_route')
                ->nullable()
                ->after('view_route');

        });
    }

    public function down()
    {
        Schema::table('document_types', function (Blueprint $table) {

            $table->dropColumn('view_own_route');

        });
    }
}