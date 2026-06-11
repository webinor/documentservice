<?php

namespace App\Managers;

use App\Models\Misc\Document;

class DocumentCreationManager
{
    public function create(
        Document $document,
        array $payload
    )//: void 
    {

        $type = $document->document_type;

        $handlerClass = $type->creation_handler_class;

if (!$handlerClass) {
    throw new \Exception(
        "Aucun handler configuré pour le type de document '{$type->name}'"
    );
}

$handler = app($handlerClass);

$handler->create(
    $document,
    $payload
);
    }
}