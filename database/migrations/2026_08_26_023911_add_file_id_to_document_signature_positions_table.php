<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('file_id')
                ->nullable()
                ->after('document_id');
        });

        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {
            $table->dropForeign([
                'file_id',
            ]);

            $table->dropColumn('file_id');
        });
    }
};