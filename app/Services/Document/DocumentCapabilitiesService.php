<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\UserServiceClient;

class DocumentCapabilitiesService
{
    protected UserServiceClient $user_service_client;

    public function __construct(UserServiceClient $user_service_client)
    {
        $this->user_service_client = $user_service_client;
    }

    public function resolve($document, $workflowContext, array $user): array
    {
        $resolver = DocumentCapabilitiesResolverFactory::make($document);

        $capabilities = $resolver->resolve(
            $document,
            $workflowContext,
            $user
        );

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

        $cancalable = $workflowContext["cancalable"] ?? false;

        if (
            ($workflowContext['isReturnedForModification'] ?? false) === true
            && $document['created_by'] == $currentUser['id']
        ) {
            $capabilities['mode'] = "edit";
            $capabilities['show_timeline'] = false;
        } else {
            $capabilities['mode'] = "view";
            $capabilities['show_timeline'] = true;
        }

        /*
        |--------------------------------------------------------------------------
        | Bypass
        |--------------------------------------------------------------------------
        */

        $capabilities["can_bypass"] = $this->canBypass(
            $workflowContext,
            $currentUser,
            $document
        );

        /*
        |--------------------------------------------------------------------------
        | Signature
        |--------------------------------------------------------------------------
        */

        $capabilities["can_sign_justificative"] = $this->canSign(
            $workflowContext,
            $currentUser,
            $document
        );

        /*
        |--------------------------------------------------------------------------
        | Workflow context
        |--------------------------------------------------------------------------
        */

        $capabilities["workflowContext"] = $workflowContext;

        /*
        |--------------------------------------------------------------------------
        | Cancel
        |--------------------------------------------------------------------------
        */

        $capabilities['can_cancel'] = true || (
            $cancalable
            && ($currentUser['id'] == $document['created_by']));

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

        if (!($workflowContext["is_active"] ?? false)) {
            return false;
        }

        return $this->user_service_client->hasPermissions(
            $document,
            $user,
            ["bypass"]
        );
    }

    public function canSign(
    array $workflowContext,
    array $user,
    array $document
): bool {

    if (!($workflowContext["is_signable"] ?? false)) {
        return false;
    }

    if (!($workflowContext["is_active"] ?? false)) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier que l'utilisateur peut agir sur l'étape courante
    |--------------------------------------------------------------------------
    */

    $stepPermissions = $workflowContext["permissions_required"] ?? [];

    if (empty($stepPermissions)) {
        return false;
    }

    if (!$this->user_service_client->hasPermissions(
        $document,
        $user,
        $stepPermissions,
        'any'
    )) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Vérifier qu'il peut signer ou valider
    |--------------------------------------------------------------------------
    */

    return $this->user_service_client->hasPermissions(
        $document,
        $user,
        ["validate", "sign"],
        'any'
    );
}
}