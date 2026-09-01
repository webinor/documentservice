<?php

namespace App\Services\Signature;

use App\Models\DocumentSignature;
use App\Models\FileSignature;
use App\Models\Misc\File;
use Illuminate\Support\Facades\DB;

class FileSignatureService
{
    /**
     * Enregistre les signatures effectivement apposées
     * sur un fichier.
     *
     * Architecture :
     *
     * document_signatures
     *     = signature métier
     *
     * file_signatures
     *     = positions physiques de cette signature
     *
     * files.signed_path
     *     = fichier PDF effectivement signé
     */
    public function recordAppliedSignatures(
        int $documentId,
        File $file,
        $positions,
        string $signedPath
    ): array {

        return DB::transaction(function () use (
            $documentId,
            $file,
            $positions,
            $signedPath
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Enregistrer le PDF signé
            |--------------------------------------------------------------------------
            */

            $file->update([
                'signed_path' => $signedPath,
                'signed_at' => now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 2. Grouper les positions par signataire
            |--------------------------------------------------------------------------
            |
            | Une personne peut avoir plusieurs positions
            | de signature dans le même fichier.
            |
            */

            $positionsBySigner =
                collect($positions)
                    ->groupBy(function ($position) {

                        return (int) $position->user_id;

                    });


            $documentSignatures = [];

            $fileSignatures = [];


            /*
            |--------------------------------------------------------------------------
            | 3. Créer une signature métier par signataire
            |--------------------------------------------------------------------------
            */

            foreach (
                $positionsBySigner
                as $userId => $signerPositions
            ) {

                /*
                |--------------------------------------------------------------------------
                | Première position du signataire
                |--------------------------------------------------------------------------
                |
                | Elle nous permet de récupérer les informations
                | communes à la signature.
                |
                */

                $firstPosition =
                    $signerPositions->first();


                $signatureType =
                    $firstPosition->signature_type
                    ?? 'SIGNATURE';


                /*
                |--------------------------------------------------------------------------
                | Signature métier
                |--------------------------------------------------------------------------
                */

                $documentSignature = DocumentSignature::updateOrCreate(
    [
        'document_id' =>
            $documentId,

        'signature_type' =>
            $signatureType,

        'signed_by' =>
            (int) $userId,
    ],
    [
        'signature_file' =>
            $firstPosition->signature_file
            ?? null,

        'signed_at' =>
            now(),

        'metadata' =>
            $firstPosition->metadata
            ?? null,
    ]
);


                $documentSignatures[] =
                    $documentSignature;


                /*
                |--------------------------------------------------------------------------
                | 4. Enregistrer toutes les positions
                |--------------------------------------------------------------------------
                */

                foreach (
                    $signerPositions
                    as $position
                ) {

                    $fileSignature =
                        FileSignature::create([
                            'file_id' =>
                                $file->id,

                            'document_signature_id' =>
                                $documentSignature->id,

                            'user_id' =>
                                (int) $userId,

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

                            'signed_at' =>
                                now(),
                        ]);


                    $fileSignatures[] =
                        $fileSignature;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Résultat
            |--------------------------------------------------------------------------
            */

            return [
                'file' =>
                    $file,

                'document_signatures' =>
                    $documentSignatures,

                'file_signatures' =>
                    $fileSignatures,
            ];
        });
    }
}