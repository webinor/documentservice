<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_signatures', function (Blueprint $table) {

            $table->unsignedBigInteger('document_signature_id')
                ->nullable()
                ->after('file_id');

            $table->foreign('document_signature_id')
                ->references('id')
                ->on('document_signatures')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_signatures', function (Blueprint $table) {

            $table->dropForeign([
                'document_signature_id',
            ]);

            $table->dropColumn(
                'document_signature_id'
            );
        });
    }
};