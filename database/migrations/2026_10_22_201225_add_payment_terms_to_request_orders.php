<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentTermsToRequestOrders extends Migration
{
    public function up()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->foreignId('payment_term_id')
                ->nullable()
                ->constrained('payment_terms')
                ->nullOnDelete();

            // Copies des informations du catalogue au moment de l'affectation.
            $table->string('payment_term_code_snapshot', 50)->nullable();
            $table->string('payment_term_name_snapshot', 150)->nullable();
            $table->text('payment_term_description_snapshot')->nullable();
            $table->string('payment_mode_snapshot', 30)->nullable();
            $table->unsignedSmallInteger('payment_due_days_snapshot')->nullable();
        });
    }

    public function down()
    {
        Schema::table('request_orders', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);

            $table->dropColumn([
                'payment_term_id',
                'payment_term_code_snapshot',
                'payment_term_name_snapshot',
                'payment_term_description_snapshot',
                'payment_mode_snapshot',
                'payment_due_days_snapshot',
            ]);
        });
    }
}