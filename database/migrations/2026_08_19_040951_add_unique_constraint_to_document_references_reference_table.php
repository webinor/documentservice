<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_references', function (Blueprint $table) {
            $table->unique('reference', 'document_references_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('document_references', function (Blueprint $table) {
            $table->dropUnique('document_references_reference_unique');
        });
    }
};