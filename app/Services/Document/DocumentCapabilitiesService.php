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
        array $currentUser
        ): array {

    // throw new \Exception($workflowContext['isReturnedForModificationnn'], 1);
    
        $cancalable = $workflowContext["cancalable"] ?? false;


        if (
            $workflowContext['isReturnedForModification'] === true
            && $document['created_by'] == $currentUser['id']
        ) {
            $capabilities['mode'] = "edit";
            $capabilities['show_timeline'] = false;

        }
        else{
            $capabilities['mode'] = "view";
            $capabilities['show_timeline'] = true;

        }

        $capabilities['can_cancel'] = $cancalable && ( $currentUser['id'] == $document['created_by'] );


        return $capabilities;
    }
}