<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use App\Services\Document\Handlers\MissionService;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class MissionDocumentHandler
implements DocumentTypeHandlerInterface
{
    protected $missionService;

    public function __construct(
        MissionService $missionService
    ){
        $this->missionService = $missionService;
    }

    public function create(
        Document $document,
        array $data
    ): void
    {
        $this->missionService->create(
            $document,
            $data
        );
    }
}