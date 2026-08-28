<?php

namespace App\Services\Signature;

use App\Models\Misc\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfSignatureService
{
    /**
     * Appose toutes les signatures d'un fichier.
     *
     * Le fichier original n'est jamais modifié.
     * Une copie signée est créée.
     */
    public function apply(
        File $file,
        $positions
    ): array {

        $positions = collect($positions);

        Log::info(
            '[SIGNATURE] ===== DEBUT APPOSITION =====',
            [
                'file_id' => $file->id,
                'positions_count' => $positions->count(),
            ]
        );

        if ($positions->isEmpty()) {

            throw new RuntimeException(
                "Aucune position de signature à appliquer pour le fichier {$file->id}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PDF original
        |--------------------------------------------------------------------------
        */

        $sourcePath =
            $this->resolveFilePath(
                $file
            );

        Log::info(
            '[SIGNATURE] Fichier original',
            [
                'file_id' => $file->id,
                'source_path' => $sourcePath,
                'exists' => file_exists($sourcePath),
            ]
        );

        if (!file_exists($sourcePath)) {

            throw new RuntimeException(
                "Fichier PDF introuvable : {$sourcePath}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FPDI
        |--------------------------------------------------------------------------
        */

        $pdf =
            new Fpdi();

        $pageCount =
            $pdf->setSourceFile(
                $sourcePath
            );

        Log::info(
            '[SIGNATURE] PDF chargé',
            [
                'file_id' => $file->id,
                'page_count' => $pageCount,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Positions par page
        |--------------------------------------------------------------------------
        */

        $positionsByPage =
            $positions->groupBy(
                'page_number'
            );

        $appliedPositions = [];


        /*
        |--------------------------------------------------------------------------
        | Recréer les pages
        |--------------------------------------------------------------------------
        */

        for (
            $pageNumber = 1;
            $pageNumber <= $pageCount;
            $pageNumber++
        ) {

            $templateId =
                $pdf->importPage(
                    $pageNumber
                );

            $size =
                $pdf->getTemplateSize(
                    $templateId
                );

            $orientation =
                $size['width'] >
                $size['height']
                    ? 'L'
                    : 'P';

            $pdf->AddPage(
                $orientation,
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate(
                $templateId
            );


            /*
            |--------------------------------------------------------------------------
            | Signatures de la page
            |--------------------------------------------------------------------------
            */

            $pagePositions =
                $positionsByPage->get(
                    $pageNumber,
                    collect()
                );

            foreach (
                $pagePositions as $position
            ) {

                Log::info(
                    '[SIGNATURE] Application position',
                    [
                        'file_id' =>
                            $file->id,

                        'position_id' =>
                            $position->id,

                        'page' =>
                            $pageNumber,

                        'x' =>
                            $position->x,

                        'y' =>
                            $position->y,

                        'width' =>
                            $position->width,

                        'height' =>
                            $position->height,
                    ]
                );

                $this->applySignature(
                    $pdf,
                    $position,
                    $size
                );

                $appliedPositions[] =
                    $position;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Vérification
        |--------------------------------------------------------------------------
        */

        if (
            count($appliedPositions)
            !== $positions->count()
        ) {

            throw new RuntimeException(
                sprintf(
                    'Toutes les signatures n\'ont pas pu être apposées sur le fichier %s.',
                    $file->id
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Génération
        |--------------------------------------------------------------------------
        */

        $outputPath =
            $this->buildOutputPath(
                $file
            );

        try {

            $pdf->Output(
                'F',
                $outputPath
            );

            if (
                !file_exists(
                    $outputPath
                )
            ) {

                throw new RuntimeException(
                    "Le PDF signé n'a pas pu être généré."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Stocker la copie
            |--------------------------------------------------------------------------
            */

            $signedPath =
                $this->storeSignedCopy(
                    $file,
                    $outputPath
                );


            Log::info(
                '[SIGNATURE] ===== FIN APPOSITION =====',
                [
                    'file_id' =>
                        $file->id,

                    'signed_path' =>
                        $signedPath,

                    'positions_count' =>
                        count(
                            $appliedPositions
                        ),
                ]
            );


            return [

                'success' =>
                    true,

                'file_id' =>
                    $file->id,

                'positions_count' =>
                    count(
                        $appliedPositions
                    ),

                'positions' =>
                    $appliedPositions,

                'signed_path' =>
                    $signedPath,
            ];

        } catch (\Throwable $e) {

            Log::error(
                '[SIGNATURE] ERREUR',
                [
                    'file_id' =>
                        $file->id,

                    'message' =>
                        $e->getMessage(),

                    'file' =>
                        $e->getFile(),

                    'line' =>
                        $e->getLine(),
                ]
            );

            if (
                file_exists(
                    $outputPath
                )
            ) {

                @unlink(
                    $outputPath
                );
            }

            throw $e;
        }
    }


    /**
     * Appose une signature.
     *
     * Les coordonnées venant de pdf.js sont en points PDF.
     * FPDI/FPDF travaille en millimètres.
     */
    protected function applySignature(
    Fpdi $pdf,
    $position,
    array $pageSize
): void {

    $signatureUrl =
        data_get(
            $position,
            'signatory.signature_url'
        );

    if (!$signatureUrl) {

        throw new RuntimeException(
            sprintf(
                'Signature introuvable pour la position %s.',
                $position->id
            )
        );
    }

    $signaturePath = null;

    try {

        /*
        |--------------------------------------------------------------------------
        | Télécharger la signature
        |--------------------------------------------------------------------------
        */

        $signaturePath =
            $this->downloadSignature(
                $signatureUrl
            );

        if (
            !file_exists($signaturePath)
            || filesize($signaturePath) <= 0
        ) {

            throw new RuntimeException(
                "Le fichier de signature téléchargé est vide."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Coordonnées reçues
        |--------------------------------------------------------------------------
        |
        | x / y / width / height viennent de PDF.js.
        |
        | PDF.js = points
        | FPDF   = mm
        |
        */

        $pdfX =
            (float) $position->x;

        $pdfY =
            (float) $position->y;

        $pdfWidth =
            (float) $position->width;

        $pdfHeight =
            (float) $position->height;

        /*
        |--------------------------------------------------------------------------
        | Conversion points PDF -> millimètres
        |--------------------------------------------------------------------------
        */

        $x =
            $pdfX * 25.4 / 72;

        $width =
            $pdfWidth * 25.4 / 72;

        $height =
            $pdfHeight * 25.4 / 72;

        /*
        |--------------------------------------------------------------------------
        | PDF.js :
        |
        | origine = bas gauche
        |
        | FPDF :
        | origine = haut gauche
        |
        |--------------------------------------------------------------------------
        */

        $pageHeight =
            (float) $pageSize['height'];

        $y =
            $pageHeight
            -
            (
                ($pdfY + $pdfHeight)
                * 25.4
                / 72
            );

        /*
        |--------------------------------------------------------------------------
        | Logs
        |--------------------------------------------------------------------------
        */

        Log::info(
            '[SIGNATURE] Coordonnées converties',
            [
                'position_id' =>
                    $position->id,

                'pdf_x' =>
                    $pdfX,

                'pdf_y' =>
                    $pdfY,

                'pdf_width' =>
                    $pdfWidth,

                'pdf_height' =>
                    $pdfHeight,

                'fpdf_x_mm' =>
                    $x,

                'fpdf_y_mm' =>
                    $y,

                'fpdf_width_mm' =>
                    $width,

                'fpdf_height_mm' =>
                    $height,

                'page_width_mm' =>
                    $pageSize['width'],

                'page_height_mm' =>
                    $pageSize['height'],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Vérifications
        |--------------------------------------------------------------------------
        */

        if ($width <= 0 || $height <= 0) {

            throw new RuntimeException(
                "Dimensions de signature invalides pour la position {$position->id}."
            );
        }

        if ($x < 0 || $x > $pageSize['width']) {

            throw new RuntimeException(
                "Position X invalide pour la signature {$position->id} : {$x} mm."
            );
        }

        if ($y < 0 || $y > $pageSize['height']) {

            throw new RuntimeException(
                "Position Y invalide pour la signature {$position->id} : {$y} mm."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Apposer la signature
        |--------------------------------------------------------------------------
        */

        Log::info(
            '[SIGNATURE] FPDF Image()',
            [
                'position_id' =>
                    $position->id,

                'x_mm' =>
                    $x,

                'y_mm' =>
                    $y,

                'width_mm' =>
                    $width,

                'height_mm' =>
                    $height,
            ]
        );

        $pdf->Image(
            $signaturePath,
            $x,
            $y,
            $width,
            $height
        );

        Log::info(
            '[SIGNATURE] Signature apposée',
            [
                'position_id' =>
                    $position->id,
            ]
        );

    } finally {

        if (
            $signaturePath
            && file_exists($signaturePath)
        ) {

            @unlink(
                $signaturePath
            );

            Log::info(
                '[SIGNATURE] Image temporaire supprimée',
                [
                    'position_id' =>
                        $position->id,
                ]
            );
        }
    }
}


    /**
     * Télécharger la signature.
     */
    protected function downloadSignature(
        string $url
    ): string {

        $response =
            Http::timeout(20)
                ->get($url);

        if (
            !$response->successful()
        ) {

            throw new RuntimeException(
                "Impossible de récupérer la signature : {$url}"
            );
        }

        $contentType =
            strtolower(
                trim(
                    explode(
                        ';',
                        (string) $response->header(
                            'Content-Type'
                        )
                    )[0]
                )
            );

        $extension =
            $this->detectImageExtension(
                $contentType
            );

        $directory =
            storage_path(
                'app/tmp/signatures'
            );

        if (
            !is_dir(
                $directory
            )
        ) {

            mkdir(
                $directory,
                0755,
                true
            );
        }

        $path =
            $directory .
            '/signature_' .
            uniqid(
                '',
                true
            ) .
            '.' .
            $extension;

        file_put_contents(
            $path,
            $response->body()
        );

        return $path;
    }


    /**
     * Extension image.
     */
    protected function detectImageExtension(
        ?string $contentType
    ): string {

        switch (
            $contentType
        ) {

            case 'image/png':
                return 'png';

            case 'image/jpeg':
            case 'image/jpg':
                return 'jpg';

            default:

                throw new RuntimeException(
                    "Format de signature non supporté : {$contentType}"
                );
        }
    }


    /**
     * Chemin physique du fichier.
     */
    protected function resolveFilePath(
        File $file
    ): string {

        return Storage::disk(
            $file->disk ?? 'public'
        )->path(
            $file->path
        );
    }


    /**
     * Chemin temporaire.
     */
    protected function buildOutputPath(
        File $file
    ): string {

        $directory =
            storage_path(
                'app/tmp/signed'
            );

        if (
            !is_dir(
                $directory
            )
        ) {

            mkdir(
                $directory,
                0755,
                true
            );
        }

        return $directory .
            '/signed_' .
            $file->id .
            '_' .
            uniqid(
                '',
                true
            ) .
            '.pdf';
    }


    /**
     * Stocker la copie signée.
     */
    protected function storeSignedCopy(
        File $file,
        string $outputPath
    ): string {

        if (
            !file_exists(
                $outputPath
            )
        ) {

            throw new RuntimeException(
                "Le fichier PDF signé est introuvable."
            );
        }

        $contents =
            file_get_contents(
                $outputPath
            );

        if (
            $contents === false
        ) {

            throw new RuntimeException(
                "Impossible de lire le PDF signé."
            );
        }

        $disk =
            Storage::disk(
                $file->disk ?? 'public'
            );

        $directory =
            'documents/signed';

        $filename =
            'signed_' .
            $file->id .
            '_' .
            uniqid(
                '',
                true
            ) .
            '.pdf';

        $signedPath =
            $directory .
            '/' .
            $filename;

        $stored =
            $disk->put(
                $signedPath,
                $contents
            );

        if (
            !$stored
        ) {

            throw new RuntimeException(
                "Impossible de sauvegarder le PDF signé."
            );
        }

        Log::info(
            '[SIGNATURE] Copie signée enregistrée',
            [
                'file_id' =>
                    $file->id,

                'signed_path' =>
                    $signedPath,

                'size' =>
                    strlen(
                        $contents
                    ),
            ]
        );

        @unlink(
            $outputPath
        );

        return $signedPath;
    }
}