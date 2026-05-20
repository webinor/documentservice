<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorMissionDatesColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('missions', function (Blueprint $table) {

            /**
             * 🔥 SUPPRESSION ANCIENNES COLONNES
             */
            $table->dropColumn([
                'start_date_planned',
                'end_date_planned',

                'start_date_actual',
                'end_date_actual',

                'departure_time_actual',
                'return_time_actual',

                'return_date',
                'departure_time_planned',

                'return_time_planned'
            ]);

            /**
             * =====================================
             * 🔵 BASE → SITE
             * =====================================
             */

            // Départ base
            $table->date('departure_date_base_planned')->nullable();
            $table->date('departure_date_base_actual')->nullable();

            $table->time('departure_time_base_planned')->nullable();
            $table->time('departure_time_base_actual')->nullable();

            // Arrivée site
            $table->date('arrival_date_site_planned')->nullable();
            $table->date('arrival_date_site_actual')->nullable();

            $table->time('arrival_time_site_planned')->nullable();
            $table->time('arrival_time_site_actual')->nullable();

            /**
             * =====================================
             * 🟣 SITE → BASE
             * =====================================
             */

            // Départ site
            $table->date('departure_date_site_planned')->nullable();
            $table->date('departure_date_site_actual')->nullable();

            $table->time('departure_time_site_planned')->nullable();
            $table->time('departure_time_site_actual')->nullable();

            // Retour base
            $table->date('arrival_date_base_planned')->nullable();
            $table->date('arrival_date_base_actual')->nullable();

            $table->time('arrival_time_base_planned')->nullable();
            $table->time('arrival_time_base_actual')->nullable();
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

            /**
             * 🔥 RESTORE OLD COLUMNS
             */
            $table->date('start_date_planned')->nullable();
            $table->date('end_date_planned')->nullable();

            $table->date('start_date_actual')->nullable();
            $table->date('end_date_actual')->nullable();

            $table->time('departure_time_actual')->nullable();
            $table->time('return_time_actual')->nullable();

            $table->date('return_date')->nullable();


            $table->time('departure_time_planned')->nullable();
            $table->time('return_time_planned')->nullable();


            /**
             * 🔥 DROP NEW COLUMNS
             */
            $table->dropColumn([

                'departure_date_base_planned',
                'departure_date_base_actual',

                'departure_time_base_planned',
                'departure_time_base_actual',

                'arrival_date_base_planned',
                'arrival_date_base_actual',

                'arrival_time_base_planned',
                'arrival_time_base_actual',

                'departure_date_site_planned',
                'departure_date_site_actual',

                'departure_time_site_planned',
                'departure_time_site_actual',

                'arrival_date_site_planned',
                'arrival_date_site_actual',

                'arrival_time_site_planned',
                'arrival_time_site_actual',
            ]);
        });
    }
}