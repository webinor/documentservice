<?php

namespace App\Services\DocumentType;

use App\Models\Misc\Document;

interface DocumentTypeHandlerInterface
{
    /**
     * Crée les données spécifiques au type de document.
     */
    public function create(
        Document $document,
        array $data
    );

    /**
     * Met à jour les données spécifiques au type de document.
     */
    public function update(
        Document $document,
        array $data
    );
}