<?php

namespace App\Services\Document;

use App\Models\Misc\Document;

class DocumentCapabilitiesService
{
    public function resolve( $document, $workflowContext, array $user): array
    {
        $resolver = DocumentCapabilitiesResolverFactory::make($document);

        return $resolver->resolve($document, $workflowContext, $user);
    }
}