<?php

namespace App\Services\Signature;

use App\Models\FileSignature;
use App\Models\Misc\File;
use Illuminate\Support\Facades\DB;

class FileSignatureService
{
    /**
     * Enregistre les signatures effectivement apposées
     * sur un fichier.
     *
     * Le PDF signé est enregistré une seule fois dans :
     *
     * files.signed_path
     *
     * Les positions/signatures sont enregistrées dans :
     *
     * file_signatures
     */
    public function recordAppliedSignatures(
        File $file,
        $positions,
        string $signedPath
    ): array {

        return DB::transaction(function () use (
            $file,
            $positions,
            $signedPath
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Enregistrer la copie signée
            |--------------------------------------------------------------------------
            */

            $file->update([
                'signed_path' => $signedPath,
                'signed_at' => now()
            ]);


            /*
            |--------------------------------------------------------------------------
            | 2. Enregistrer les signatures
            |--------------------------------------------------------------------------
            */

            $results = [];

            foreach ($positions as $position) {

                $fileSignature =
                    FileSignature::updateOrCreate(

                        [
                            'file_id' =>
                                $file->id,

                            'user_id' =>
                                (int) $position->user_id,

                            'page' =>
                                (int) $position->page_number,

                            'x' =>
                                (float) $position->x,

                            'y' =>
                                (float) $position->y,

                            'width' =>
                                (float) $position->width,

                            'height' =>
                                (float) $position->height,
                        ],

                        [
                            'signed_at' =>
                                now(),
                        ]
                    );


                $results[] =
                    $fileSignature;
            }


            return $results;
        });
    }
}