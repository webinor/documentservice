<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveSignedPathFromFileSignaturesToFilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Ajouter signed_path dans files
        |--------------------------------------------------------------------------
        */

        Schema::table('files', function (Blueprint $table) {

            $table
                ->string('signed_path')
                ->nullable()
                ->after('path');

        });


        /*
        |--------------------------------------------------------------------------
        | 2. Migrer les chemins existants
        |--------------------------------------------------------------------------
        |
        | Plusieurs lignes file_signatures peuvent appartenir au même fichier
        | et contenir exactement le même signed_path.
        |
        | On récupère donc une seule valeur par file_id.
        |
        */

        $signatures = DB::table('file_signatures')
            ->select(
                'file_id',
                'signed_path'
            )
            ->whereNotNull('signed_path')
            ->where('signed_path', '!=', '')
            ->orderBy('id')
            ->get();

        foreach ($signatures as $signature) {

            DB::table('files')
                ->where('id', $signature->file_id)
                ->where(function ($query) {

                    $query
                        ->whereNull('signed_path')
                        ->orWhere('signed_path', '');

                })
                ->update([
                    'signed_path' => $signature->signed_path,
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Supprimer signed_path de file_signatures
        |--------------------------------------------------------------------------
        */

        Schema::table('file_signatures', function (Blueprint $table) {

            $table->dropColumn('signed_path');

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Restaurer signed_path dans file_signatures
        |--------------------------------------------------------------------------
        */

        Schema::table('file_signatures', function (Blueprint $table) {

            $table
                ->string('signed_path')
                ->nullable()
                ->after('height');

        });


        /*
        |--------------------------------------------------------------------------
        | 2. Restaurer les chemins signés
        |--------------------------------------------------------------------------
        |
        | Un même fichier peut avoir plusieurs signatures.
        | On remet donc le même signed_path sur chacune des signatures
        | du fichier.
        |
        */

        DB::table('file_signatures')
            ->join(
                'files',
                'files.id',
                '=',
                'file_signatures.file_id'
            )
            ->whereNotNull('files.signed_path')
            ->update([
                'file_signatures.signed_path' =>
                    DB::raw('files.signed_path'),
            ]);


        /*
        |--------------------------------------------------------------------------
        | 3. Supprimer signed_path de files
        |--------------------------------------------------------------------------
        */

        Schema::table('files', function (Blueprint $table) {

            $table->dropColumn('signed_path');

        });
    }
}
