<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('enrichment_handler_class')
                ->nullable()
                ->after('creation_handler_class');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('enrichment_handler_class');
        });
    }
};