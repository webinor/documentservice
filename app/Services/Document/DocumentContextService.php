<?php

namespace App\Services\Document;

use App\Models\Misc\Document;

class DocumentContextService
{
    public function resolve( $document, $workflowContext, array $user): array
    {
        // return ["oui"];

        $resolver = DocumentContextResolverFactory::make($document);

        // return ["oui 2"];


        return $resolver->resolve($document, $workflowContext, $user);
    }
}