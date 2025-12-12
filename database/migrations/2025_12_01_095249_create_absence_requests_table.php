<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbsenceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('beneficiary');
            $table->string('reason');
            $table->boolean("duties_handover")->nullable();
            $table->text("handover_details")->nullable();
    $table->date("departure_date"); // date only
$table->time("departure_time"); // heure only
$table->date("return_date");    // date only
$table->time("return_time");    // heure only
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('absence_requests');
    }
}
