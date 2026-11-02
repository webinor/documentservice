<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomPaymentSnapshotsToRequestOrders extends Migration
{
    public function up()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->boolean('has_custom_payment_terms_snapshot')
                ->default(false);

            $table->json('payment_installments_snapshot')->nullable();
        });
    }

    public function down()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropColumn([
                'has_custom_payment_terms_snapshot',
                'payment_installments_snapshot',
            ]);
        });
    }
}