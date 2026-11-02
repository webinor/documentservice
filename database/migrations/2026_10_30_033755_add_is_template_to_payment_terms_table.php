<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsTemplateToPaymentTermsTable extends Migration
{
    public function up()
    {
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->boolean('is_template')
                ->default(false)
                ->after('description');
        });
    }

    public function down()
    {
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->dropColumn('is_template');
        });
    }
}