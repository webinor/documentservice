<?php

namespace App\Services\TaxiPaper;



use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class TaxiPaperDocumentHandler
implements DocumentTypeHandlerInterface
{
    protected TaxiPaperService $taxiPaperService;

    public function __construct(
        TaxiPaperService $taxiPaperService
    ){
        $this->taxiPaperService = $taxiPaperService;
    }

    public function create(
        Document $document,
        array $data
    ): void
    {
        $this->taxiPaperService->create(
            $document,
            $data
        );
    }
}