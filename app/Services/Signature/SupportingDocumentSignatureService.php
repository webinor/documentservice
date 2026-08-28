<?php

namespace App\Services\Signature;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class SupportingDocumentSignatureService
{
    protected SignaturePositionService $positionService;

    protected PdfSignatureService $pdfSignatureService;

    protected FileSignatureService $fileSignatureService;

    public function __construct(
        SignaturePositionService $positionService,
        PdfSignatureService $pdfSignatureService,
        FileSignatureService $fileSignatureService
    ) {
        $this->positionService =
            $positionService;

        $this->pdfSignatureService =
            $pdfSignatureService;

        $this->fileSignatureService =
            $fileSignatureService;
    }

    /**
     * Appose les signatures sur les pièces
     * justificatives d'un document.
     */
    public function apply(
        string $documentUuid
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Récupération des fichiers + positions enrichies
        |--------------------------------------------------------------------------
        */

        $files =
            $this->positionService
                ->getFilesWithPositions(
                    $documentUuid
                );

        if ($files->isEmpty()) {

            return [
                'success' => true,
                'message' =>
                    'Aucune position de signature à traiter.',
                'files' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION AVANT MODIFICATION
        |--------------------------------------------------------------------------
        |
        | On vérifie TOUT avant d'apposer la première signature.
        |
        */

        foreach ($files as $fileData) {

            $fileId =
                $fileData['file_id'];

            $positions =
                $fileData['positions'];

            foreach ($positions as $position) {

                if (
                    empty(
                        $position->signatory
                    )
                ) {

                    throw new RuntimeException(
                        sprintf(
                            'Signataire introuvable pour la position %s du fichier %s.',
                            $position->id,
                            $fileId
                        )
                    );
                }

                if (
                    empty(
                        $position
                            ->signatory['signature_url']
                    )
                ) {

                    throw new RuntimeException(
                        sprintf(
                            'Signature introuvable pour le signataire %s du fichier %s.',
                            $position->signatory['name']
                                ?? $position->user_id,
                            $fileId
                        )
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPOSITION
        |--------------------------------------------------------------------------
        */

        $results = [];

        foreach ($files as $fileData) {

            $file =
                $fileData['file'];

            $positions =
                $fileData['positions'];

            /*
            |--------------------------------------------------------------------------
            | 1. Modifier physiquement le PDF
            |--------------------------------------------------------------------------
            */

            $pdfResult =
                $this->pdfSignatureService
                    ->apply(
                        $file,
                        $positions
                    );

            /*
            |--------------------------------------------------------------------------
            | 2. Enregistrer les appositions
            |--------------------------------------------------------------------------
            */

            $fileSignatures =
                $this->fileSignatureService
                    ->recordAppliedSignatures(
                        $file,
                        $positions,
                        $pdfResult['signed_path']
                    );

            $results[] = [

                'file_id' =>
                    $file->id,

                'positions_count' =>
                    $positions->count(),

                'file_signatures_count' =>
                    count($fileSignatures),

                'signed_path' =>
                    $pdfResult['signed_path'],

                'pdf' =>
                    $pdfResult,
            ];
        }

        return [

            'success' =>
                true,

            'document_uuid' =>
                $documentUuid,

            'files' =>
                $results,
        ];
    }
}