<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {

            // rename existing fields
            $table->renameColumn('start_date', 'start_date_planned');
            $table->renameColumn('end_date', 'end_date_planned');

        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {

            $table->renameColumn('start_date_planned', 'start_date');
            $table->renameColumn('end_date_planned', 'end_date');


        });
    }
};