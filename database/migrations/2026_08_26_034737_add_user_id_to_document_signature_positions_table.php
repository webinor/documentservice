<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToDocumentSignaturePositionsTable extends Migration
{
    public function up()
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->after('file_id');
        });
    }

    public function down()
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}