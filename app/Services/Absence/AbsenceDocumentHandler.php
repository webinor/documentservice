<?php

namespace App\Services\Absence;


use App\Models\Misc\Document;
use App\Services\Absence\AbsenceService;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class AbsenceDocumentHandler implements DocumentTypeHandlerInterface
{
    protected AbsenceService $absenceService;

    public function __construct(
        AbsenceService $absenceService
    ){
        $this->absenceService = $absenceService;
    }

    public function create(
        Document $document,
        array $data
    ): void
    {
        $this->absenceService->create(
            $document,
            $data
        );
    }

   
}