<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderTypeToInvoiceProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_providers', function (Blueprint $table) {
            $table->enum('provider_type', [
                'IT_SUPPLIER',
                'MEDICAL_SUPPLIER',
                'IT_PROVIDER',
                'MEDICAL_PROVIDER',
            ])->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_providers', function (Blueprint $table) {
            $table->dropColumn('provider_type');
        });
    }
}