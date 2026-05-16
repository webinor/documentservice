<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->enum('type',["MANUAL" , "TIME_BASED" , "LIMIT_ONLY"])
            ->default('MANUAL')
            ->after('name');
            // MANUAL | TIME_BASED | LIMIT_ONLY
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};