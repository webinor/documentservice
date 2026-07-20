<?php

namespace App\Services\Document\Resolvers;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;

class DefaultCapabilitiesResolver
implements DocumentCapabilitiesResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array {

        return [

            "document_actions" => [
                "DOWNLOAD" => true,
            ],

            "business_actions" => [

            ]

        ];

    }
}