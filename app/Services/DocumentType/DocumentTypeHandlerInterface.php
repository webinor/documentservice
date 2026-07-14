<?php

namespace App\Services\DocumentType;

use App\Models\Misc\Document;

interface DocumentTypeHandlerInterface
{
    public function create(
        Document $document,
        array $data
    );
}