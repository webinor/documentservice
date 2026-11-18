<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentTriggerModesTable extends Migration
{
    public function up()
    {
        Schema::create('payment_trigger_modes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('payment_trigger_id');

            // Mode de paiement autorisé :
            // DIRECT_SUPPLIER ou ASSIGNED_AGENT
            $table->string('payment_mode', 50);

            $table->timestamps();

            // Un même mode ne peut être associé deux fois au même trigger.
            $table->unique(
                ['payment_trigger_id', 'payment_mode'],
                'payment_trigger_mode_unique'
            );

            $table->foreign('payment_trigger_id')
                ->references('id')
                ->on('payment_triggers')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_trigger_modes');
    }
}