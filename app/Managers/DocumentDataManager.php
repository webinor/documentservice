<?php

namespace App\Managers;

use App\Models\Misc\Document;

class DocumentDataManager
{
//     public function create(
//         Document $document,
//         array $payload
//     )//: void 
//     {

//         $type = $document->document_type;

//         $handlerClass = $type->creation_handler_class;

// if (!$handlerClass) {
//     throw new \Exception(
//         "Aucun handler de creation configuré pour le type de document '{$type->name}'"
//     );
// }

// $handler = app($handlerClass);

// $handler->create(
//     $document,
//     $payload
// );
//     }



    public function create(Document $document, array $payload): void
{
    $handler = $this->resolveHandler($document);

    $handler->create($document, $payload);
}

public function update(Document $document, array $payload): void
{
    $handler = $this->resolveHandler($document);

    $handler->update($document, $payload);
}

private function resolveHandler(Document $document)
{
    $type = $document->document_type;

    $handlerClass = $type->creation_handler_class;

    if (!$handlerClass) {
        throw new \Exception(
            "Aucun handler configuré pour le type de document '{$type->name}'"
        );
    }

    return app($handlerClass);
}

}