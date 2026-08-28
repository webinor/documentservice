<?php

namespace App\Services\Signature;

use App\Models\DocumentSignaturePosition;
use App\Models\Misc\Document;
use Illuminate\Support\Facades\Log;

class SignaturePositionRequirementService
{
    /**
     * Vérifie que l'utilisateur a positionné sa signature
     * sur chaque page de chaque justificatif d'une fiche
     * de régularisation.
     *
     * Parcours métier :
     *
     * Document
     *    ↓
     * RegularizationSheet
     *    ↓
     * RegularizationReceipt
     *    ↓
     * File (type = RECEIPT)
     *    ↓
     * DocumentSignaturePosition
     *
     * Une page est considérée comme positionnée si une ligne
     * existe dans document_signature_positions avec :
     *
     * document_id
     * file_id
     * user_id
     * signature_type = RECEIPT
     * page_number
     */
    public function checkAllReceiptPagesPositioned(
        string $documentUuid,
        int $userId
    ): array {

        /*
        |--------------------------------------------------------------------------
        | START
        |--------------------------------------------------------------------------
        */

        Log::info(
            '[SIGNATURE_POSITION] ===== START CHECK =====',
            [
                'document_uuid' => $documentUuid,
                'user_id' => $userId,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 1. Document
        |--------------------------------------------------------------------------
        */

        $document = Document::query()
            ->where('uuid', $documentUuid)
            ->with([
                'regularization_sheet.receipts.file',
            ])
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Document introuvable
        |--------------------------------------------------------------------------
        */

        if (!$document) {

            Log::warning(
                '[SIGNATURE_POSITION] Document not found',
                [
                    'document_uuid' => $documentUuid,
                    'user_id' => $userId,
                ]
            );

            return [
                'document_id' => null,
                'document_uuid' => $documentUuid,
                'user_id' => $userId,

                'all_pages_positioned' => false,

                'receipts_count' => 0,
                'files_count' => 0,

                'total_pages' => 0,
                'positioned_pages' => 0,
                'missing_pages_count' => 0,

                'missing_pages' => [],
                'invalid_files' => [],

                'message' =>
                    'Document introuvable.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 2. Fiche de régularisation
        |--------------------------------------------------------------------------
        */

        $sheet = $document->regularization_sheet;


        if (!$sheet) {

            Log::warning(
                '[SIGNATURE_POSITION] Regularization sheet not found',
                [
                    'document_uuid' => $documentUuid,
                    'document_id' => $document->id,
                    'user_id' => $userId,
                ]
            );

            return [
                'document_id' => $document->id,
                'document_uuid' => $documentUuid,
                'user_id' => $userId,

                'all_pages_positioned' => false,

                'receipts_count' => 0,
                'files_count' => 0,

                'total_pages' => 0,
                'positioned_pages' => 0,
                'missing_pages_count' => 0,

                'missing_pages' => [],
                'invalid_files' => [],

                'message' =>
                    'Aucune fiche de régularisation associée au document.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. Justificatifs
        |--------------------------------------------------------------------------
        */

        $receipts = $sheet->receipts;


        if ($receipts->isEmpty()) {

            Log::info(
                '[SIGNATURE_POSITION] No receipts found',
                [
                    'document_uuid' => $documentUuid,
                    'document_id' => $document->id,
                    'sheet_id' => $sheet->id,
                    'user_id' => $userId,
                ]
            );

            return [
                'document_id' => $document->id,
                'document_uuid' => $documentUuid,
                'user_id' => $userId,

                'all_pages_positioned' => false,

                'receipts_count' => 0,
                'files_count' => 0,

                'total_pages' => 0,
                'positioned_pages' => 0,
                'missing_pages_count' => 0,

                'missing_pages' => [],
                'invalid_files' => [],

                'message' =>
                    'Aucun justificatif associé à la fiche de régularisation.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Fichiers des justificatifs
        |--------------------------------------------------------------------------
        |
        | RegularizationReceipt::file()
        |
        | retourne uniquement :
        |
        | type = RECEIPT
        |
        */

        $receiptFiles = $receipts
            ->map(function ($receipt) {

                return [
                    'receipt' => $receipt,
                    'file' => $receipt->file,
                ];

            })
            ->filter(function ($data) {

                return $data['file'] !== null;

            })
            ->values();


        /*
        |--------------------------------------------------------------------------
        | 5. Aucun fichier
        |--------------------------------------------------------------------------
        */

        if ($receiptFiles->isEmpty()) {

            Log::warning(
                '[SIGNATURE_POSITION] No receipt files found',
                [
                    'document_uuid' => $documentUuid,
                    'document_id' => $document->id,
                    'sheet_id' => $sheet->id,
                    'receipts_count' => $receipts->count(),
                    'user_id' => $userId,
                ]
            );

            return [
                'document_id' => $document->id,
                'document_uuid' => $documentUuid,
                'user_id' => $userId,

                'all_pages_positioned' => false,

                'receipts_count' => $receipts->count(),
                'files_count' => 0,

                'total_pages' => 0,
                'positioned_pages' => 0,
                'missing_pages_count' => 0,

                'missing_pages' => [],
                'invalid_files' => [],

                'message' =>
                    'Aucun fichier de justificatif trouvé.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 6. IDs des fichiers concernés
        |--------------------------------------------------------------------------
        */

        $fileIds = $receiptFiles
            ->pluck('file.id')
            ->filter()
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | 7. Positions de signature
        |--------------------------------------------------------------------------
        |
        | IMPORTANT :
        |
        | On filtre par :
        |
        | document
        | user
        | signature_type
        |
        | Puis on récupère uniquement file_id/page_number.
        |
        */

        $positions = DocumentSignaturePosition::query()
            ->where('document_id', $document->id)
            ->where('user_id', $userId)
            ->where('signature_type', 'RECEIPT')
            ->whereIn('file_id', $fileIds)
            ->get([
                'file_id',
                'page_number',
            ]);


        /*
        |--------------------------------------------------------------------------
        | 8. Index des pages positionnées
        |--------------------------------------------------------------------------
        |
        | Exemple :
        |
        | 5:1
        | 5:2
        | 5:3
        | 8:1
        |
        */

        $positionedPages = [];

        foreach ($positions as $position) {

            $key =
                $position->file_id
                . ':'
                . $position->page_number;

            $positionedPages[$key] = true;
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Vérification de toutes les pages
        |--------------------------------------------------------------------------
        */

        $totalFiles = 0;

        $totalPages = 0;

        $positionedPagesCount = 0;

        $missingPages = [];

        $invalidFiles = [];


        foreach ($receiptFiles as $receiptFile) {

            $receipt = $receiptFile['receipt'];

            $file = $receiptFile['file'];

            $totalFiles++;


            /*
            |--------------------------------------------------------------------------
            | Nombre de pages
            |--------------------------------------------------------------------------
            */

            $pageCount = (int) $file->page_count;


            /*
            |--------------------------------------------------------------------------
            | Fichier invalide
            |--------------------------------------------------------------------------
            */

            if ($pageCount <= 0) {

                $invalidFiles[] = [

                    'receipt_id' =>
                        $receipt->id,

                    'file_id' =>
                        $file->id,

                    'file_name' =>
                        $file->name
                        ?? $file->original_name
                        ?? null,

                    'page_count' =>
                        $pageCount,

                    'reason' =>
                        'Le nombre de pages du fichier est invalide.',
                ];

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Vérification page par page
            |--------------------------------------------------------------------------
            */

            for (
                $pageNumber = 1;
                $pageNumber <= $pageCount;
                $pageNumber++
            ) {

                $totalPages++;

                $key =
                    $file->id
                    . ':'
                    . $pageNumber;


                /*
                |--------------------------------------------------------------------------
                | Position trouvée
                |--------------------------------------------------------------------------
                */

                if (
                    isset($positionedPages[$key])
                ) {

                    $positionedPagesCount++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Position manquante
                |--------------------------------------------------------------------------
                */

                $missingPages[] = [

                    'receipt_id' =>
                        $receipt->id,

                    'file_id' =>
                        $file->id,

                    'file_name' =>
                        $file->name
                        ?? $file->original_name
                        ?? null,

                    'page_number' =>
                        $pageNumber,

                    'reason' =>
                        'Position de signature manquante.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 10. Résultat final
        |--------------------------------------------------------------------------
        */

        $allPagesPositioned =
            $totalPages > 0
            && empty($missingPages)
            && empty($invalidFiles);


        /*
        |--------------------------------------------------------------------------
        | 11. Tracking
        |--------------------------------------------------------------------------
        */

        Log::info(
            '[SIGNATURE_POSITION] ===== CHECK RESULT =====',
            [
                'document_uuid' =>
                    $documentUuid,

                'document_id' =>
                    $document->id,

                'user_id' =>
                    $userId,

                'sheet_id' =>
                    $sheet->id,

                'receipts_count' =>
                    $receipts->count(),

                'files_count' =>
                    $totalFiles,

                'signature_positions_count' =>
                    $positions->count(),

                'total_pages' =>
                    $totalPages,

                'positioned_pages' =>
                    $positionedPagesCount,

                'missing_pages_count' =>
                    count($missingPages),

                'invalid_files_count' =>
                    count($invalidFiles),

                'all_pages_positioned' =>
                    $allPagesPositioned,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 12. Tracking des pages manquantes
        |--------------------------------------------------------------------------
        */

        if (!empty($missingPages)) {

            Log::warning(
                '[SIGNATURE_POSITION] Missing signature positions',
                [
                    'document_uuid' =>
                        $documentUuid,

                    'document_id' =>
                        $document->id,

                    'user_id' =>
                        $userId,

                    'missing_pages' =>
                        $missingPages,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Tracking des fichiers invalides
        |--------------------------------------------------------------------------
        */

        if (!empty($invalidFiles)) {

            Log::error(
                '[SIGNATURE_POSITION] Invalid receipt files',
                [
                    'document_uuid' =>
                        $documentUuid,

                    'document_id' =>
                        $document->id,

                    'user_id' =>
                        $userId,

                    'invalid_files' =>
                        $invalidFiles,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | END
        |--------------------------------------------------------------------------
        */

        Log::info(
            '[SIGNATURE_POSITION] ===== END CHECK =====',
            [
                'document_uuid' =>
                    $documentUuid,

                'user_id' =>
                    $userId,

                'all_pages_positioned' =>
                    $allPagesPositioned,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 14. Retour
        |--------------------------------------------------------------------------
        */

        return [

            'document_id' =>
                $document->id,

            'document_uuid' =>
                $documentUuid,

            'user_id' =>
                $userId,

            'all_pages_positioned' =>
                $allPagesPositioned,

            'receipts_count' =>
                $receipts->count(),

            'files_count' =>
                $totalFiles,

            'signature_positions_count' =>
                $positions->count(),

            'total_pages' =>
                $totalPages,

            'positioned_pages' =>
                $positionedPagesCount,

            'missing_pages_count' =>
                count($missingPages),

            'missing_pages' =>
                $missingPages,

            'invalid_files' =>
                $invalidFiles,
        ];
    }
}