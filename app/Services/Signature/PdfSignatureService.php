<?php

namespace App\Services\Signature;

use App\Models\Misc\File;
use App\Services\UserServiceClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfSignatureService
{
    protected UserServiceClient $userService;

    /**
     * PdfSignatureService constructor.
     */
    public function __construct(
        UserServiceClient $userService
    ) {
        $this->userService = $userService;
    }

    /**
     * Appose toutes les signatures d'un fichier.
     *
     * Le fichier original n'est jamais modifié.
     *
     * Le bloc ajouté au PDF contient :
     *
     * Signé par
     * [signature]
     * Nom du signataire
     * DD/MM/YYYY à HH:MM
     *
     * Les coordonnées de la zone sont celles
     * enregistrées dans document_signature_positions.
     *
     * @param File $file
     * @param mixed $positions
     *
     * @return array
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

        /*
        |--------------------------------------------------------------------------
        | Vérification
        |--------------------------------------------------------------------------
        */

        if ($positions->isEmpty()) {

            throw new RuntimeException(
                "Aucune position de signature à appliquer pour le fichier {$file->id}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Récupérer les IDs utilisateurs
        |--------------------------------------------------------------------------
        */

        $userIds = $positions
            ->pluck('user_id')
            ->filter()
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values()
            ->toArray();

        if (empty($userIds)) {

            throw new RuntimeException(
                "Aucun utilisateur associé aux positions de signature."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Récupérer les signatures/utilisateurs
        |--------------------------------------------------------------------------
        |
        | Une seule requête vers UserService.
        |
        */

        $users =
            $this->userService->getUsersSignatures(
                $userIds
            );

        /*
        |--------------------------------------------------------------------------
        | Indexation par user_id
        |--------------------------------------------------------------------------
        */

        $usersById =
            collect($users)
                ->keyBy('id')
                ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Enrichissement des positions
        |--------------------------------------------------------------------------
        */

        $positions =
            $positions->map(
                function ($position) use ($usersById) {

                    $userId =
                        (int) $position->user_id;

                    $user =
                        $usersById[$userId] ?? null;

                    if (!$user) {

                        throw new RuntimeException(
                            "Informations du signataire introuvables pour l'utilisateur {$userId}."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | URL de signature
                    |--------------------------------------------------------------------------
                    */

                    $position->signature_url =
                        data_get(
                            $user,
                            'signature_url'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Nom complet
                    |--------------------------------------------------------------------------
                    */

                    $position->signer_name =
                        data_get(
                            $user,
                            'nom_complet'
                        )
                        ??
                        data_get(
                            $user,
                            'name'
                        )
                        ??
                        'Signataire';

                    /*
                    |--------------------------------------------------------------------------
                    | Informations utiles
                    |--------------------------------------------------------------------------
                    */

                    $position->user_data =
                        $user;

                    return $position;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Fichier PDF original
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
        | Initialisation FPDI
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
        | Positions groupées par page
        |--------------------------------------------------------------------------
        */

        $positionsByPage =
            $positions->groupBy(
                'page_number'
            );

        $appliedPositions = [];

        /*
        |--------------------------------------------------------------------------
        | Recréation des pages
        |--------------------------------------------------------------------------
        */

        for (
            $pageNumber = 1;
            $pageNumber <= $pageCount;
            $pageNumber++
        ) {

            /*
            |--------------------------------------------------------------------------
            | Import de la page
            |--------------------------------------------------------------------------
            */

            $templateId =
                $pdf->importPage(
                    $pageNumber
                );

            /*
            |--------------------------------------------------------------------------
            | Dimensions de la page
            |--------------------------------------------------------------------------
            */

            $size =
                $pdf->getTemplateSize(
                    $templateId
                );

            /*
            |--------------------------------------------------------------------------
            | Orientation
            |--------------------------------------------------------------------------
            */

            $orientation =
                $size['width'] > $size['height']
                    ? 'L'
                    : 'P';

            /*
            |--------------------------------------------------------------------------
            | Ajouter la page
            |--------------------------------------------------------------------------
            */

            $pdf->AddPage(
                $orientation,
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Ajouter le contenu original
            |--------------------------------------------------------------------------
            */

            $pdf->useTemplate(
                $templateId
            );

            /*
            |--------------------------------------------------------------------------
            | Positions de signature de cette page
            |--------------------------------------------------------------------------
            */

            $pagePositions =
                $positionsByPage->get(
                    $pageNumber,
                    collect()
                );

            /*
            |--------------------------------------------------------------------------
            | Apposition des signatures
            |--------------------------------------------------------------------------
            */

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

                        'user_id' =>
                            $position->user_id,

                        'x' =>
                            $position->x,

                        'y' =>
                            $position->y,

                        'width' =>
                            $position->width,

                        'height' =>
                            $position->height,

                        'signer_name' =>
                            $position->signer_name,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Appliquer le bloc complet
                |--------------------------------------------------------------------------
                */

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
                    "Toutes les signatures n'ont pas pu être apposées sur le fichier %s.",
                    $file->id
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Génération du fichier temporaire
        |--------------------------------------------------------------------------
        */

        $outputPath =
            $this->buildOutputPath(
                $file
            );

        try {

            /*
            |--------------------------------------------------------------------------
            | Générer le PDF
            |--------------------------------------------------------------------------
            */

            $pdf->Output(
                'F',
                $outputPath
            );

            /*
            |--------------------------------------------------------------------------
            | Vérification
            |--------------------------------------------------------------------------
            */

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
            | Stockage
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

            /*
            |--------------------------------------------------------------------------
            | Résultat
            |--------------------------------------------------------------------------
            */

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
                '[SIGNATURE] ERREUR GENERATION PDF',
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

            /*
            |--------------------------------------------------------------------------
            | Nettoyage
            |--------------------------------------------------------------------------
            */

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
     * Appose une signature complète.
     *
     * Bloc :
     *
     * Signé par
     * [signature]
     * Nom
     * Date + heure
     *
     * @param Fpdi $pdf
     * @param mixed $position
     * @param array $pageSize
     *
     * @return void
     */
    protected function applySignature(
        Fpdi $pdf,
        $position,
        array $pageSize
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Informations du signataire
        |--------------------------------------------------------------------------
        */

        $signatureUrl =
            $position->signature_url
            ?? null;

        if (!$signatureUrl) {

            throw new RuntimeException(
                sprintf(
                    "Signature introuvable pour l'utilisateur %s.",
                    $position->user_id
                )
            );
        }

        $signerName =
            $position->signer_name
            ?? 'Signataire';

        /*
        |--------------------------------------------------------------------------
        | Date de signature
        |--------------------------------------------------------------------------
        */

        $signedAt =
            now();

        /*
        |--------------------------------------------------------------------------
        | Télécharger l'image
        |--------------------------------------------------------------------------
        */

        $signaturePath = null;

        try {

            $signaturePath =
                $this->downloadSignature(
                    $signatureUrl
                );

            /*
            |--------------------------------------------------------------------------
            | Vérification du fichier
            |--------------------------------------------------------------------------
            */

            if (
                !file_exists($signaturePath)
                ||
                filesize($signaturePath) <= 0
            ) {

                throw new RuntimeException(
                    "Le fichier de signature téléchargé est vide."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Coordonnées PDF.js
            |--------------------------------------------------------------------------
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
            | Conversion points PDF -> mm
            |--------------------------------------------------------------------------
            */

            $x =
                $pdfX *
                25.4 /
                72;

            $width =
                $pdfWidth *
                25.4 /
                72;

            $height =
                $pdfHeight *
                25.4 /
                72;

            /*
            |--------------------------------------------------------------------------
            | Conversion Y
            |--------------------------------------------------------------------------
            |
            | PDF.js :
            |
            | origine en bas à gauche
            |
            | FPDF :
            |
            | origine en haut à gauche
            |
            */

            $pageHeight =
                (float) $pageSize['height'];

            $y =
                $pageHeight
                -
                (
                    (
                        $pdfY +
                        $pdfHeight
                    )
                    *
                    25.4
                    /
                    72
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

                    'user_id' =>
                        $position->user_id,

                    'signer_name' =>
                        $signerName,

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
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Vérifications
            |--------------------------------------------------------------------------
            */

            if (
                $width <= 0
                ||
                $height <= 0
            ) {

                throw new RuntimeException(
                    "Dimensions invalides pour la signature {$position->id}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Dessiner le bloc complet
            |--------------------------------------------------------------------------
            */

            $scale = 1.75;

            $this->drawSignatureBlock(
                $pdf,
                $signaturePath,
                $signerName,
                $signedAt,
                $x,
                $y,
                $width,
                $height,
                $scale
            );

        } finally {

            /*
            |--------------------------------------------------------------------------
            | Supprimer le fichier temporaire
            |--------------------------------------------------------------------------
            */

            if (
                $signaturePath
                &&
                file_exists($signaturePath)
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
     * Dessine le bloc visuel complet.
     *
     * Ordre :
     *
     * Signé par
     * Signature
     * Nom
     * Date + heure
     *
     * Aucun fond ni aucune bordure.
     *
     * @param Fpdi $pdf
     * @param string $signaturePath
     * @param string $signerName
     * @param \Carbon\Carbon $signedAt
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     *
     * @return void
     */
    protected function drawSignatureBlock(
    Fpdi $pdf,
    string $signaturePath,
    string $signerName,
    $signedAt,
    float $x,
    float $y,
    float $width,
    float $height,
    float $scale = 1.0
): void {

    /*
    |--------------------------------------------------------------------------
    | SCALE GLOBAL DU BLOC
    |--------------------------------------------------------------------------
    |
    | 1.00 = taille normale
    | 1.10 = +10%
    | 1.25 = +25%
    | 1.50 = +50%
    |
    */

    $scale = max(0.1, $scale);


    /*
    |--------------------------------------------------------------------------
    | Agrandir depuis le centre
    |--------------------------------------------------------------------------
    |
    | Cela évite que le bloc parte uniquement vers la droite
    | et vers le bas.
    |
    */

    $scaledWidth =
        $width * $scale;

    $scaledHeight =
        $height * $scale;

    $x =
        $x -
        (($scaledWidth - $width) / 2);

    $y =
        $y -
        (($scaledHeight - $height) / 2);

    $width =
        $scaledWidth;

    $height =
        $scaledHeight;


    /*
    |--------------------------------------------------------------------------
    | Padding
    |--------------------------------------------------------------------------
    */

    $padding =
        max(
            1,
            min(
                2,
                $height * 0.03
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Dimensions internes
    |--------------------------------------------------------------------------
    */

    $innerX =
        $x + $padding;

    $innerWidth =
        max(
            1,
            $width - ($padding * 2)
        );

    $currentY =
        $y + $padding;

    $innerHeight =
        max(
            1,
            $height - ($padding * 2)
        );


    /*
    |--------------------------------------------------------------------------
    | Hauteur du texte "Signé par"
    |--------------------------------------------------------------------------
    */

    $signedByHeight =
        $this->calculateTextHeight(
            $innerHeight,
            0.12
        );


    /*
    |--------------------------------------------------------------------------
    | Hauteur du nom
    |--------------------------------------------------------------------------
    */

    $nameHeight =
        $this->calculateTextHeight(
            $innerHeight,
            0.14
        );


    /*
    |--------------------------------------------------------------------------
    | Hauteur date
    |--------------------------------------------------------------------------
    */

    $dateHeight =
        $this->calculateTextHeight(
            $innerHeight,
            0.12
        );


    /*
    |--------------------------------------------------------------------------
    | Espacement
    |--------------------------------------------------------------------------
    */

    $spacing =
        max(
            0.5,
            min(
                1.5,
                $innerHeight * 0.02
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Réserver l'espace texte
    |--------------------------------------------------------------------------
    */

    $reservedTextHeight =
        $signedByHeight
        +
        $nameHeight
        +
        $dateHeight
        +
        ($spacing * 3);


    /*
    |--------------------------------------------------------------------------
    | Espace disponible pour la signature
    |--------------------------------------------------------------------------
    */

    $signatureAreaHeight =
        max(
            2,
            $innerHeight -
            $reservedTextHeight
        );


    /*
    |--------------------------------------------------------------------------
    | 1. SIGNÉ PAR
    |--------------------------------------------------------------------------
    */

    $this->drawCenteredText(
        $pdf,
        $this->pdfText('Signé par'),
        $innerX,
        $currentY,
        $innerWidth,
        $signedByHeight,
        8 * $scale,
        false
    );

    $currentY +=
        $signedByHeight +
        $spacing;


    /*
    |--------------------------------------------------------------------------
    | 2. IMAGE DE SIGNATURE
    |--------------------------------------------------------------------------
    */

    $this->drawSignatureImage(
        $pdf,
        $signaturePath,
        $innerX,
        $currentY,
        $innerWidth,
        $signatureAreaHeight,
        $scale
    );

    $currentY +=
        $signatureAreaHeight +
        $spacing;


    /*
    |--------------------------------------------------------------------------
    | 3. NOM DU SIGNATAIRE
    |--------------------------------------------------------------------------
    */

    $this->drawCenteredText(
        $pdf,
        $this->pdfText($signerName),
        $innerX,
        $currentY,
        $innerWidth,
        $nameHeight,
        9 * $scale,
        true
    );

    $currentY +=
        $nameHeight +
        $spacing;


    /*
    |--------------------------------------------------------------------------
    | 4. DATE + HEURE
    |--------------------------------------------------------------------------
    */

    $dateText =
        $this->formatSignatureDate(
            $signedAt
        );

    $this->drawCenteredText(
        $pdf,
        $this->pdfText($dateText),
        $innerX,
        $currentY,
        $innerWidth,
        $dateHeight,
        8 * $scale,
        false
    );
}


    /**
     * Dessine l'image de signature en conservant son ratio.
     *
     * @param Fpdi $pdf
     * @param string $signaturePath
     * @param float $x
     * @param float $y
     * @param float $availableWidth
     * @param float $availableHeight
     *
     * @return void
     */

    protected function drawSignatureImage(
    Fpdi $pdf,
    string $signaturePath,
    float $x,
    float $y,
    float $availableWidth,
    float $availableHeight,
    float $scale = 1.0
): void {

    /*
    |--------------------------------------------------------------------------
    | SCALE
    |--------------------------------------------------------------------------
    */

    $scale = max(0.1, $scale);


    /*
    |--------------------------------------------------------------------------
    | Dimensions originales
    |--------------------------------------------------------------------------
    */

    $imageInfo = @getimagesize($signaturePath);

    if (!$imageInfo) {
        throw new RuntimeException(
            "Impossible de déterminer les dimensions de la signature."
        );
    }

    $imageWidth = (float) $imageInfo[0];
    $imageHeight = (float) $imageInfo[1];

    if (
        $imageWidth <= 0 ||
        $imageHeight <= 0
    ) {
        throw new RuntimeException(
            "Dimensions de signature invalides."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ratio
    |--------------------------------------------------------------------------
    */

    $ratio = $imageWidth / $imageHeight;


    /*
    |--------------------------------------------------------------------------
    | Taille maximale normale
    |--------------------------------------------------------------------------
    */

    $imageDisplayWidth = $availableWidth;
    $imageDisplayHeight = $imageDisplayWidth / $ratio;


    /*
    |--------------------------------------------------------------------------
    | Ajustement hauteur
    |--------------------------------------------------------------------------
    */

    if ($imageDisplayHeight > $availableHeight) {

        $imageDisplayHeight = $availableHeight;

        $imageDisplayWidth =
            $imageDisplayHeight * $ratio;
    }


    /*
    |--------------------------------------------------------------------------
    | Appliquer le SCALE
    |--------------------------------------------------------------------------
    |
    | On agrandit/réduit l'image autour de son centre.
    |
    */

    $scaledWidth =
        $imageDisplayWidth * $scale;

    $scaledHeight =
        $imageDisplayHeight * $scale;


    /*
    |--------------------------------------------------------------------------
    | Ne jamais dépasser la zone disponible
    |--------------------------------------------------------------------------
    |
    | Si le scale provoque un dépassement,
    | on réduit automatiquement l'image pour rester
    | dans la zone de signature.
    |
    */

    if (
        $scaledWidth > $availableWidth ||
        $scaledHeight > $availableHeight
    ) {

        $scaleByWidth =
            $availableWidth / $imageDisplayWidth;

        $scaleByHeight =
            $availableHeight / $imageDisplayHeight;

        $effectiveScale =
            min(
                $scale,
                $scaleByWidth,
                $scaleByHeight
            );

        $scaledWidth =
            $imageDisplayWidth * $effectiveScale;

        $scaledHeight =
            $imageDisplayHeight * $effectiveScale;
    }


    /*
    |--------------------------------------------------------------------------
    | Centrage horizontal
    |--------------------------------------------------------------------------
    */

    $imageX =
        $x +
        (
            $availableWidth -
            $scaledWidth
        ) / 2;


    /*
    |--------------------------------------------------------------------------
    | Centrage vertical
    |--------------------------------------------------------------------------
    */

    $imageY =
        $y +
        (
            $availableHeight -
            $scaledHeight
        ) / 2;


    /*
    |--------------------------------------------------------------------------
    | Image
    |--------------------------------------------------------------------------
    */

    $pdf->Image(
        $signaturePath,
        $imageX,
        $imageY,
        $scaledWidth,
        $scaledHeight
    );
}
    protected function OlddrawSignatureImage(
        Fpdi $pdf,
        string $signaturePath,
        float $x,
        float $y,
        float $availableWidth,
        float $availableHeight
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Dimensions originales
        |--------------------------------------------------------------------------
        */

        $imageInfo =
            @getimagesize(
                $signaturePath
            );

        if (!$imageInfo) {

            throw new RuntimeException(
                "Impossible de déterminer les dimensions de la signature."
            );
        }

        $imageWidth =
            (float) $imageInfo[0];

        $imageHeight =
            (float) $imageInfo[1];

        if (
            $imageWidth <= 0
            ||
            $imageHeight <= 0
        ) {

            throw new RuntimeException(
                "Dimensions de signature invalides."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ratio
        |--------------------------------------------------------------------------
        */

        $ratio =
            $imageWidth /
            $imageHeight;

        /*
        |--------------------------------------------------------------------------
        | Dimensions maximales
        |--------------------------------------------------------------------------
        */

        $imageDisplayWidth =
            $availableWidth;

        $imageDisplayHeight =
            $imageDisplayWidth /
            $ratio;

        /*
        |--------------------------------------------------------------------------
        | Ajuster si trop haut
        |--------------------------------------------------------------------------
        */

        if (
            $imageDisplayHeight >
            $availableHeight
        ) {

            $imageDisplayHeight =
                $availableHeight;

            $imageDisplayWidth =
                $imageDisplayHeight *
                $ratio;
        }

        /*
        |--------------------------------------------------------------------------
        | Centrage horizontal
        |--------------------------------------------------------------------------
        */

        $imageX =
            $x +
            (
                $availableWidth -
                $imageDisplayWidth
            ) / 2;

        /*
        |--------------------------------------------------------------------------
        | Centrage vertical
        |--------------------------------------------------------------------------
        */

        $imageY =
            $y +
            (
                $availableHeight -
                $imageDisplayHeight
            ) / 2;

        /*
        |--------------------------------------------------------------------------
        | Image
        |--------------------------------------------------------------------------
        */

        $pdf->Image(
            $signaturePath,
            $imageX,
            $imageY,
            $imageDisplayWidth,
            $imageDisplayHeight
        );
    }


    /**
     * Dessine un texte centré horizontalement.
     *
     * @param Fpdi $pdf
     * @param string $text
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param float $fontSize
     * @param bool $bold
     *
     * @return void
     */
    protected function drawCenteredText(
        Fpdi $pdf,
        string $text,
        float $x,
        float $y,
        float $width,
        float $height,
        float $fontSize,
        bool $bold = false
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Police
        |--------------------------------------------------------------------------
        |
        | FPDF utilise Helvetica par défaut.
        |
        */

        $font =
            $bold
                ? 'Helvetica'
                : 'Helvetica';

        $style =
            $bold
                ? 'B'
                : '';

        /*
        |--------------------------------------------------------------------------
        | Couleur
        |--------------------------------------------------------------------------
        */

        if ($bold) {

            /*
            | Nom du signataire
            */

            $pdf->SetTextColor(
                40,
                40,
                40
            );

        } else {

            /*
            | Signé par / date
            */

            $pdf->SetTextColor(
                100,
                100,
                100
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Taille
        |--------------------------------------------------------------------------
        */

        $pdf->SetFont(
            $font,
            $style,
            $fontSize
        );

        /*
        |--------------------------------------------------------------------------
        | Largeur approximative du texte
        |--------------------------------------------------------------------------
        */

        $textWidth =
            $pdf->GetStringWidth(
                $text
            );

        /*
        |--------------------------------------------------------------------------
        | Centrage
        |--------------------------------------------------------------------------
        */

        $textX =
            $x +
            max(
                0,
                (
                    $width -
                    $textWidth
                ) / 2
            );

        /*
        |--------------------------------------------------------------------------
        | Position verticale
        |--------------------------------------------------------------------------
        */

        $lineHeight =
            max(
                2,
                $height
            );

        /*
        |--------------------------------------------------------------------------
        | Texte
        |--------------------------------------------------------------------------
        */

        $pdf->SetXY(
            $textX,
            $y
        );

        $pdf->Cell(
            $textWidth,
            $lineHeight,
            $text,
            0,
            0,
            'C'
        );
    }


    /**
     * Calcule une hauteur de texte adaptée à la hauteur du bloc.
     *
     * @param float $height
     * @param float $ratio
     *
     * @return float
     */
    protected function calculateTextHeight(
        float $height,
        float $ratio
    ): float {

        return max(
            2.5,
            min(
                5,
                $height * $ratio
            )
        );
    }


    /**
     * Télécharge la signature depuis UserService.
     *
     * @param string $url
     *
     * @return string
     */
    protected function downloadSignature(
        string $url
    ): string {

        Log::info(
            '[SIGNATURE] Téléchargement signature',
            [
                'url' => $url,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Requête HTTP
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Content-Type
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Extension
        |--------------------------------------------------------------------------
        */

        $extension =
            $this->detectImageExtension(
                $contentType
            );

        /*
        |--------------------------------------------------------------------------
        | Répertoire temporaire
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Nom fichier
        |--------------------------------------------------------------------------
        */

        $path =
            $directory .
            '/signature_' .
            uniqid(
                '',
                true
            ) .
            '.' .
            $extension;

        /*
        |--------------------------------------------------------------------------
        | Sauvegarde
        |--------------------------------------------------------------------------
        */

        $written =
            file_put_contents(
                $path,
                $response->body()
            );

        if (
            $written === false
        ) {

            throw new RuntimeException(
                "Impossible d'enregistrer la signature temporaire."
            );
        }

        Log::info(
            '[SIGNATURE] Signature téléchargée',
            [
                'path' =>
                    $path,

                'content_type' =>
                    $contentType,

                'size' =>
                    $written,
            ]
        );

        return $path;
    }


    /**
     * Détermine l'extension d'une image.
     *
     * @param string|null $contentType
     *
     * @return string
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
     * Résout le chemin physique du fichier.
     *
     * @param File $file
     *
     * @return string
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
     * Construit le chemin temporaire du PDF signé.
     *
     * @param File $file
     *
     * @return string
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
     * Stocke le PDF signé.
     *
     * @param File $file
     * @param string $outputPath
     *
     * @return string
     */
    protected function storeSignedCopy(
        File $file,
        string $outputPath
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Vérification
        |--------------------------------------------------------------------------
        */

        if (
            !file_exists(
                $outputPath
            )
        ) {

            throw new RuntimeException(
                "Le fichier PDF signé est introuvable."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Lire le fichier
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Storage
        |--------------------------------------------------------------------------
        */

        $disk =
            Storage::disk(
                $file->disk ?? 'public'
            );

        /*
        |--------------------------------------------------------------------------
        | Répertoire
        |--------------------------------------------------------------------------
        */

        $directory =
            'documents/signed';

        /*
        |--------------------------------------------------------------------------
        | Nom
        |--------------------------------------------------------------------------
        */

        $filename =
            'signed_' .
            $file->id .
            '_' .
            uniqid(
                '',
                true
            ) .
            '.pdf';

        /*
        |--------------------------------------------------------------------------
        | Chemin
        |--------------------------------------------------------------------------
        */

        $signedPath =
            $directory .
            '/' .
            $filename;

        /*
        |--------------------------------------------------------------------------
        | Stockage
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Log
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Supprimer le temporaire
        |--------------------------------------------------------------------------
        */

        @unlink(
            $outputPath
        );

        return $signedPath;
    }

    /**
 * Convertit un texte UTF-8 vers l'encodage attendu par FPDF.
 */
protected function pdfText(string $text): string
{
    return iconv(
        'UTF-8',
        'windows-1252//TRANSLIT//IGNORE',
        $text
    ) ?: '';
}

protected function formatSignatureDate(
    \DateTimeInterface $date
): string {
    return $date->format('d/m/Y') .
        ' à ' .
        $date->format('H:i');
}
} 