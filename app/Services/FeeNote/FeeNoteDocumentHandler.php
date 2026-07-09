<?php

namespace App\Services\FeeNote;


use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class FeeNoteDocumentHandler implements DocumentTypeHandlerInterface
{
    protected FeeNoteService $feeNoteService;

    public function __construct(
        FeeNoteService $feeNoteService
    ){
        $this->feeNoteService = $feeNoteService;
    }

    public function create(
        Document $document,
        array $data
    ): void
    {
        $this->feeNoteService->create(
            $document,
            $data
        );
    }
}