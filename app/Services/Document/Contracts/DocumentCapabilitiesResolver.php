<?php

namespace App\Services\Document\Contracts;

use App\Models\Misc\Document;

interface DocumentCapabilitiesResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array;
}