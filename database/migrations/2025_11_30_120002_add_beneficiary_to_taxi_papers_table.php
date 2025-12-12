<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBeneficiaryToTaxiPapersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('taxi_papers', function (Blueprint $table) {
        
            $table->unsignedBigInteger('beneficiary')->after('id');
        
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('taxi_papers', function (Blueprint $table) {
        
            $table->dropColumn('beneficiary');
        
        });
    }
}
