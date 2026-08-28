<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {

           
            $table->unique(
                [
                    'document_id',
                    'file_id',
                    'user_id',
                    'signature_type',
                    'page_number',
                ],
                'doc_signature_position_page_unique'
            );


        //          DB::statement(
        //     'ALTER TABLE `document_signature_positions`
        //      DROP INDEX `doc_signature_position_user_unique`'
        // );


        });
    }

    public function down(): void
    {
        Schema::table('document_signature_positions', function (Blueprint $table) {

        // $table->unique(
        //             [
        //                 'document_id',
        //                 'signature_type',
        //                 'user_id',
        //             ],
        //             'doc_signature_position_user_unique'
        //         );


            $table->dropUnique(
                'doc_signature_position_page_unique'
            );
        });
    }
};