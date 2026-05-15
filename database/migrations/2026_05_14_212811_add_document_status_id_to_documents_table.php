<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentStatusIdToDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('documents', function (Blueprint $table) {

            $table->unsignedBigInteger('document_status_id')
                ->nullable()
                ->after('document_type_id');

            $table->foreign('document_status_id')
                ->references('id')
                ->on('document_statuses')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {

            $table->dropForeign(['document_status_id']);

            $table->dropColumn('document_status_id');
        });
    }
}