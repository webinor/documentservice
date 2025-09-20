<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttachmentTypeCategoryToAttachmentType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attachment_types', function (Blueprint $table) {

            $table->unsignedInteger('attachment_type_category_id')->after('slug');
              /*  $table->foreignId('attachment_type_category_id')
                ->after('name')
                  ->constrained('attachment_type_categories')
                  ->cascadeOnDelete();          // Un code appartient à un type
                  */
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
            
                // D'abord drop la contrainte
   // $table->dropForeign(['attachment_type_category_id']);

    // Ensuite drop la colonne
    $table->dropColumn('attachment_type_category_id');
        });
    }
}
