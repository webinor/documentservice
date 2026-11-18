<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidAmountToRequestOrderPaymentSchedulesTable extends Migration
{
    public function up()
{
    Schema::table('request_order_payment_schedules', function (Blueprint $table) {
        $table->decimal('paid_amount', 18, 2)
            ->default(0)
            ->after('amount');
    });
}

public function down()
{
    Schema::table('request_order_payment_schedules', function (Blueprint $table) {
        $table->dropColumn('paid_amount');
    });
}
}
