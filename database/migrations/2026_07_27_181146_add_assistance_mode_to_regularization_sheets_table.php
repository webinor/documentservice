<?php

use App\Enums\AssistanceMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssistanceModeToRegularizationSheetsTable extends Migration
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
        'assistance_mode',
        AssistanceMode::values())
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
            $table->dropColumn('assistance_mode');
        });
    }
}