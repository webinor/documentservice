<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUniqueConstraintOnDocumentSignaturePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table(
        'document_signature_positions',
        function (Blueprint $table) {


          $table->unique(
                [
                    'document_id',
                    'signature_type',
                    'user_id',
                ],
                'doc_signature_position_user_unique'
            );


            $table->dropUnique(
                'doc_signature_position_unique'
            );

          
        }
    );
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down()
{
    Schema::table(
        'document_signature_positions',
        function (Blueprint $table) {


         $table->unique(
                [
                    'document_id',
                    'signature_type',
                ],
                'doc_signature_position_unique'
            );

            
            $table->dropUnique(
                'doc_signature_position_user_unique'
            );

           
        }
    );
}
}
