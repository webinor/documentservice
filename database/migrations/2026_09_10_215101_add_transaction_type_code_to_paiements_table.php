<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTransactionTypeCodeToPaiementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_type_code', 100)
                ->nullable()
                ->after('id');

            $table->index(
                'transaction_type_code',
                'payments_transaction_type_code_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(
                'payments_transaction_type_code_idx'
            );

            $table->dropColumn('transaction_type_code');
        });
    }
}