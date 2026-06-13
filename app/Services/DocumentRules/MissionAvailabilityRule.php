<?php

use App\Contracts\DocumentAvailabilityRuleInterface;
use App\Models\Misc\Document;

class MissionAvailabilityRule implements DocumentAvailabilityRuleInterface
{
    public function canDownload(Document $document): bool
    {
        return true  ;/*WorkflowInstanceStep::query()
            ->where('workflow_instance_id', $document->workflow_instance_id)
            ->whereHas('workflowStep', function ($q) {
                $q->where('code', 'MANAGER_APPROVAL');
            })
            ->where('status', 'COMPLETE')
            ->exists();*/
    }

    // public function getReason(Document $document): ?string
    // {
    //     if (!$this->canDownload($document)) {
    //         return "En attente de validation du manager.";
    //     }

    //     return null;
    // }
}