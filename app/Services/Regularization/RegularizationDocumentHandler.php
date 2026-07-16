<?php

namespace App\Services\Regularization;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class RegularizationDocumentHandler implements DocumentTypeHandlerInterface
{
    protected RegularizationService $regularizationService;

    public function __construct(
        RegularizationService $regularizationService
    ){
        $this->regularizationService = $regularizationService;
    }


     public function create(Document $document,array $data)
    {
        $this->regularizationService->create(
            $document,
            $data
        );
    }
   

}