<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActualAndTimeFieldsToMissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('missions', function (Blueprint $table) {

    $table->date('start_date_actual')->nullable();
    $table->date('end_date_actual')->nullable();

    $table->time('departure_time_planned')->nullable();
    $table->time('return_time_planned')->nullable();

    $table->time('departure_time_actual')->nullable();
    $table->time('return_time_actual')->nullable();
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('missions', function (Blueprint $table) {
            //


                        $table->dropColumn([
                'start_date_actual',
                'end_date_actual',
                'departure_time_planned',
                'return_time_planned',
                'departure_time_actual',
                'return_time_actual'
            ]);
            
        });
    }
}
