<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('leave_types', function (Blueprint $table) {
    $table->id();

    $table->string('code')->unique();
    $table->string('name');

    // Catégorie
    $table->enum('category', [
        'ANNUAL',
        'SICK',
        'MATERNITY',
        'PATERNITY',
        'EXCEPTIONAL',
        'UNPAID'
    ]);

    // Nombre de jours accordés
    // $table->integer('default_days')->nullable();

    // Payé ou non
    $table->boolean('is_paid')->default(true);

    // Justificatif obligatoire
    $table->boolean('requires_attachment')->default(false);

    // Nécessite validation RH
    $table->boolean('requires_hr_validation')->default(true);

    $table->boolean('is_active')->default(true);

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
        Schema::dropIfExists('leave_types');
    }
}