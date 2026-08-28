<?php

namespace App\Services\Signature;

use App\Models\DocumentSignature;
use App\Models\FileSignature;
use Illuminate\Support\Facades\DB;

class SignatureRecordService
{
    /**
     * Enregistre la signature métier du document.
     *
     * Une signature métier est unique par :
     *
     * document
     * + type
     * + signataire
     */
    public function recordDocumentSignature(
        int $documentId,
        string $signatureType,
        int $userId,
        string $signatureFile,
        array $metadata = []
    ): DocumentSignature {

        return DocumentSignature::updateOrCreate(

            [
                'document_id' =>
                    $documentId,

                'signature_type' =>
                    $signatureType,

                'signed_by' =>
                    $userId,
            ],

            [
                'signature_file' =>
                    $signatureFile,

                'signed_at' =>
                    now(),

                'metadata' =>
                    !empty($metadata)
                        ? $metadata
                        : null,
            ]
        );
    }

    /**
     * Enregistre l'apposition physique
     * sur un fichier.
     */
    public function recordFileSignature(
        int $fileId,
        int $userId,
        int $page,
        float $x,
        float $y,
        float $width,
        float $height,
        string $signedPath
    ): FileSignature {

        return FileSignature::updateOrCreate(

            [
                'file_id' =>
                    $fileId,

                'user_id' =>
                    $userId,

                'page' =>
                    $page,

                'x' =>
                    $x,

                'y' =>
                    $y,

                'width' =>
                    $width,

                'height' =>
                    $height,
            ],

            [
                'signed_path' =>
                    $signedPath,

                'signed_at' =>
                    now(),
            ]
        );
    }

    /**
     * Enregistrer une apposition complète.
     */
  public function record(
    int $documentId,
    $position,
    string $signatureFile,
    string $signedPath,
    array $metadata = []
): array {

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT SIGNATURE
        |--------------------------------------------------------------------------
        */

        $documentSignature =
            $this->recordDocumentSignature(
                $documentId,
                $position->signature_type,
                (int) $position->user_id,
                $signatureFile,
                $metadata
            );


        /*
        |--------------------------------------------------------------------------
        | FILE SIGNATURE
        |--------------------------------------------------------------------------
        */

        $fileSignature =
            $this->recordFileSignature(
                (int) $position->file_id,
                (int) $position->user_id,
                (int) $position->page_number,
                (float) $position->x,
                (float) $position->y,
                (float) $position->width,
                (float) $position->height,
                $signedPath
            );


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        // DB::commit();


        return [
            'document_signature' =>
                $documentSignature,

            'file_signature' =>
                $fileSignature,
        ];

    } catch (\Throwable $e) {

        /*
        |--------------------------------------------------------------------------
        | ROLLBACK
        |--------------------------------------------------------------------------
        */

        DB::rollBack();

        throw $e;
    }
}
}