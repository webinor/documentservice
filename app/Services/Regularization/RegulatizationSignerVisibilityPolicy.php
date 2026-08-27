<?php

namespace App\Services\Regularization;



use App\Contracts\SignerVisibilityPolicy;
use App\Services\FinancialDocumentSignerVisibilityPolicy;

class RegulatizationSignerVisibilityPolicy extends FinancialDocumentSignerVisibilityPolicy
{
    protected function isSpecificRuleSatisfied(
        array $participant,
        array $documentData
    ): bool {
        $creator = $participant['user'] ?? null;

        return $creator
            && ($participant['source_type'] ?? null) === 'OWNER'
            && ($creator['employee_id'] ?? null) !== ($documentData['actor_id'] ?? null);
    }
}