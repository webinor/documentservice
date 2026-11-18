<?php

namespace App\Contracts\Signature;

use App\Models\Misc\Document;
use Illuminate\Support\Collection;

interface ReceiptSourceResolverInterface
{
    /**
     * Indique si ce résolveur prend en charge le document.
     */
    public function supports(Document $document): bool;

    /**
     * Retourne les justificatifs et leurs fichiers.
     *
     * Format :
     *
     * [
     *     [
     *         'source' => $receipt,
     *         'file' => $file,
     *     ],
     * ]
     *
     * Le fichier peut être null si le justificatif
     * ne possède pas encore de fichier.
     */
    public function resolve(Document $document): Collection;
}