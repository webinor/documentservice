<?php

namespace App\Services\DocumentRules;

use App\Contracts\DocumentAvailabilityRuleInterface;
use App\Contracts\WorkflowAvailabilityClient;
use App\Models\Misc\Document;

class TaxiPaperAvailabilityRule implements DocumentAvailabilityRuleInterface
{
    private WorkflowAvailabilityClient $workflowClient;

    public function __construct(
        WorkflowAvailabilityClient $workflowClient
    ) {
        $this->workflowClient = $workflowClient;
    }
    public function canDownload(Document $document): bool
    {
        $context = $this->workflowClient
            ->getDocumentContext($document->id);

        $hasSettlementSignature =
            collect($context["signatures"])
                ->contains(
                    fn ($signature) =>
                    $signature["code"] ===
                        "TAXI_PAPER_SETTLEMENT"
                    &&
                    $signature["signed"]
                );

        return
            $context["workflow_status"] === "COMPLETE"
            &&
            $hasSettlementSignature;
    }

    public function getReason(Document $document): ?string
    {
        if (!$this->canDownload($document)) {
            return "En attente de confirmation du bénéficiaire.";
        }

        return null;
    }
}