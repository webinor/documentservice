<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToAbsenceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
           $table->enum('type', ['CONGE', 'PERMISSION'])
                    ->default('CONGE')
                  ->comment('CONGE ou PERMISSION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}