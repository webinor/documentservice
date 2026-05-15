<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeStatusNotNullableInDocumentsTable extends Migration
{
    public function up()
    {
        // 1. Sécuriser les anciennes données
        DB::table('documents')
            ->whereNull('status')
            ->update(['status' => 'DRAFT']);

        // 2. Rendre NOT NULL
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status')
                // ->default('DRAFT')
                ->nullable(false)
                ->change();
        });
    }

    public function down()
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('status')
                ->nullable()
                ->change();
        });
    }
}