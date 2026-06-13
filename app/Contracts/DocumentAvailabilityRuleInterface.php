<?php

namespace App\Contracts;

use App\Models\Misc\Document;

interface DocumentAvailabilityRuleInterface
{
    public function canDownload(Document $document): bool;

    // public function getReason(Document $document): ?string;
}