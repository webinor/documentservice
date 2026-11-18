<?php

namespace App\Services\Common;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;

class PdfPageCounter
{
    /**
     * Déterminer le nombre de pages d'un PDF.
     *
     * Retourne null si le fichier n'est pas un PDF
     * ou si le nombre de pages ne peut pas être déterminé.
     */
    public function getPageCount(UploadedFile $file): ?int
    {
        if (
            strtolower($file->getClientOriginalExtension()) !== 'pdf'
        ) {
            return null;
        }

        try {
            $parser = new Parser();

            $pdf = $parser->parseFile(
                $file->getRealPath()
            );

            return count($pdf->getPages());

        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}