<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkCalendarsTable extends Migration
{
    public function up()
    {
        Schema::create('work_calendars', function (Blueprint $table) {

            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            // société / organisation
            $table->unsignedBigInteger('organization_id')->nullable();

            $table->boolean('is_default')->default(false);

            $table->text('description')->nullable();

            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('work_calendars');
    }
}