<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsInitialPaymentToPaymentTriggersTable extends Migration
{
    public function up()
    {
        Schema::table('payment_triggers', function (Blueprint $table) {
            $table->boolean('is_initial_payment')
                ->default(false)
                ->after('description');
        });
    }

    public function down()
    {
        Schema::table('payment_triggers', function (Blueprint $table) {
            $table->dropColumn('is_initial_payment');
        });
    }
}