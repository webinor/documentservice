<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentModeFieldsToRequestOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('request_orders', function (Blueprint $table) {
        $table->enum('payment_mode', [
            'DIRECT_SUPPLIER',
            'ASSIGNED_AGENT',
        ])->nullable()->after('status');

        $table->unsignedBigInteger('payment_agent_employee_id')
            ->nullable()
            ->after('payment_mode');
    });
}

public function down()
{
    Schema::table('request_orders', function (Blueprint $table) {
        $table->dropColumn([
            'payment_mode',
            'payment_agent_employee_id',
        ]);
    });
}
}
