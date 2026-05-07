<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDocumentIdToMissionsTable extends Migration
{
    public function up()
    {
        Schema::table('missions', function (Blueprint $table) {

            $table->foreignId('document_id')
                  ->after('id')
                  ->constrained('documents')
                  ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('missions', function (Blueprint $table) {

            $table->dropForeign(['document_id']);
            $table->dropColumn('document_id');
        });
    }
}