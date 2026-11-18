<?php

namespace App\Services\Mission;

use App\Models\Misc\Document;
use App\Services\DocumentType\DocumentTypeHandlerInterface;

class MissionDocumentHandler implements DocumentTypeHandlerInterface
{
    /**
     * @var MissionService
     */
    protected $missionService;

    public function __construct(MissionService $missionService)
    {
        $this->missionService = $missionService;
    }

    /**
     * Crée les informations spécifiques à une mission.
     */
    public function create(
        Document $document,
        array $data
    ): void {
        $this->missionService->create(
            $document,
            $data
        );
    }

    /**
     * Met à jour les informations spécifiques à une mission.
     */
    public function update(
        Document $document,
        array $data
    ): void {
        $this->missionService->update(
            $document,
            $data
        );
    }
}