<?php

namespace App\Services\TaxiPaper;

use App\Contracts\SignerVisibilityPolicy;
use App\Services\FinancialDocumentSignerVisibilityPolicy;

class TaxiSignerVisibilityPolicy  extends FinancialDocumentSignerVisibilityPolicy
{

protected function isSpecificRuleSatisfied(
        array $participant,
        array $documentData
    ): bool {
        $creator = $participant['user'] ?? null;

        return $creator
            && ($participant['source_type'] ?? null) !== 'OWNER'
            && ($creator['employee_id'] ?? null) !== ($documentData['actor_id'] ?? null);
    }
    
}