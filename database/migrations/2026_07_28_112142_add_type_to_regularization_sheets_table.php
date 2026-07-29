<?php

use App\Enums\RegularizationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToRegularizationSheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
             $table->enum(
        'regularization_type',
        RegularizationType::values())
                ->nullable()
                ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('regularization_sheets', function (Blueprint $table) {
            $table->dropColumn('regularization_type');
        });
    }
}
