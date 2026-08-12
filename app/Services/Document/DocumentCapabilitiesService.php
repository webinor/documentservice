<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;

class DocumentCapabilitiesService
{
    protected UserServiceClient $user_service_client;

    public function __construct(UserServiceClient $user_service_client) {
        $this->user_service_client = $user_service_client;
    }
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


    // throw new \Exception($workflowContext, 1);


          $capabilities["can_bypass"] = $this->canBypass(
        $workflowContext,
        $currentUser,
        $document
    );

    $capabilities["workflowContext"] = $workflowContext;

        $capabilities['can_cancel'] = $cancalable && ( $currentUser['id'] == $document['created_by'] );


        return $capabilities;
    }


    public function canBypass(
    array $workflowContext,
    array $user,
    array $document
): bool {

    if (!($workflowContext["is_bypassable"] ?? false)) {
        return false;
    }

    if (!$workflowContext["is_active"]) {
        return false;
    }

    return $this->user_service_client->hasBypassPermission($document , $user ,  ["bypass"]);
}

}