<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentTermIdToSupplierQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            // $table->unsignedBigInteger('payment_term_id')
            //     ->nullable()
            //     ->after('delivery_days');

            $table->foreign('payment_term_id')
                ->references('id')
                ->on('payment_terms')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->dropForeign(['payment_term_id']);
            // $table->dropColumn('payment_term_id');
        });
    }
}