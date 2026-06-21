<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeReferenceNotNullableInMissionsTable extends Migration
{
    public function up()
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });
    }
}