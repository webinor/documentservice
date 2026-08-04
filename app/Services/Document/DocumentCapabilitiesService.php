<?php

namespace App\Services\Document;

use App\Models\Misc\Document;

class DocumentCapabilitiesService
{
    public function resolve( $document, $workflowContext, array $user): array
    {
        $resolver = DocumentCapabilitiesResolverFactory::make($document);

        $capabilities = $resolver->resolve($document, $workflowContext, $user);

          return $this->applyWorkflowCapabilities(
        $capabilities,
        $document,
        $workflowContext,
        $user
    );
    }

      protected function applyWorkflowCapabilities(
        array $capabilities,
        $document,
        $workflowContext,
        array $user
    ): array {

    // throw new \Exception($workflowContext['isReturnedForModificationnn'], 1);
    

        if (
            $workflowContext['isReturnedForModification'] === true
            && $document['created_by'] == $user['id']
        ) {
            $capabilities['mode'] = "edit";
            $capabilities['show_timeline'] = false;

        }
        else{
            $capabilities['mode'] = "view";
            $capabilities['show_timeline'] = true;

        }


        return $capabilities;
    }
}