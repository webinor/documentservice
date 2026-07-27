<?php

namespace App\Services\Document\Resolvers;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;
use App\Services\Document\Contracts\DocumentContextResolver;

class DefaultContextResolver
implements DocumentContextResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array {

        return [

            "signatures" => [],

        ];

    }
}