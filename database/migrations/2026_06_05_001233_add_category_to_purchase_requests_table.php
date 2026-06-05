<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToPurchaseRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {

            $table->enum('category', [
                'IT_EQUIPMENT',
                'SOFTWARE',
                'OFFICE_SUPPLY',
                'FURNITURE',
                'VEHICLE',
                'TELECOM',
                'SERVICE',
                'OTHER'
            ])
            ->nullable()
            ->after('description');

        });
    }

    public function down()
    {
        Schema::table('purchase_requests', function (Blueprint $table) {

            $table->dropColumn('category');

        });
    }
}