<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttachmentNumberRequiredToAttachmentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attachment_types', function (Blueprint $table) {
          $table->boolean('attachment_number_required')
          //->default(false)
          ->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attachment_types', function (Blueprint $table) {
        $table->dropColumn('attachment_number_required');

        });
    }
}
