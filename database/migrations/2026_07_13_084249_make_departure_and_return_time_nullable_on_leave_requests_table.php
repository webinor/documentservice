<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeDepartureAndReturnTimeNullableOnLeaveRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->time('departure_time')->nullable()->change();
            $table->time('return_time')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('absence_requests', function (Blueprint $table) {
            $table->time('departure_time')->nullable(false)->change();
            $table->time('return_time')->nullable(false)->change();
        });
    }
}