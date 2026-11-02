<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_term_id')
                ->nullable()
                ->index();

            $table->boolean('has_custom_payment_terms')
                ->default(false);
        });
    }

    public function down()
    {
        Schema::table('purchase_supplier_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'payment_term_id',
                'has_custom_payment_terms',
            ]);
        });
    }
};