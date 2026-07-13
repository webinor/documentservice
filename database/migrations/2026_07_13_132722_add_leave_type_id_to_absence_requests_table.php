<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveTypeIdToAbsenceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('absence_requests', function (Blueprint $table) {

            $table->foreignId('leave_type_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('leave_types')
                  ->cascadeOnDelete();

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

            $table->dropForeign([
                'leave_type_id'
            ]);

            $table->dropColumn('leave_type_id');

        });
    }
}