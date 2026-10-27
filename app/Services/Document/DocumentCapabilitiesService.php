<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\ResponsibilityService;
use App\Services\UserServiceClient;
use Exception;

class DocumentCapabilitiesService
{
    protected UserServiceClient $user_service_client;
    protected ResponsibilityService $responsibilityService;

    public function __construct(UserServiceClient $user_service_client,ResponsibilityService $responsibilityService)
    {
        $this->user_service_client = $user_service_client;
        $this->responsibilityService = $responsibilityService;
    }

    public function resolve(array $document, array $workflowContext, array $user): array
    {

        $resolver = DocumentCapabilitiesResolverFactory::make($document);

        $capabilities = $resolver->resolve(
            $document,
            $workflowContext,
            $user
        );

        //  $user = request()->get('user');
    // throw new Exception(json_encode($user), 1);

        return $this->applyWorkflowCapabilities(
            $capabilities,
            $document,
            $workflowContext,
            $user
        );
    }

    protected function applyWorkflowCapabilities(
        array $capabilities,
        array $document,
        array $workflowContext,
        array $currentUser
    ): array {

        $cancalable = $workflowContext["cancalable"] ?? false;

           $user = request()->get('user');


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
        | validation etape
        |--------------------------------------------------------------------------
        */

        $capabilities["can_intervene"] = $this->canIntervene(
            $workflowContext,
            $currentUser,
            $document
        );

        $capabilities["can_define_purchase_type"] =  $this->canDefinePurchaseType(
            $workflowContext,
            $user,
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

        $capabilities['can_cancel'] =
            $cancalable
            && ($currentUser['id'] == $document['created_by']);


           

      


    // return
    $responsibilities =
    $user['employeeContext']['responsibilities'] ?? [];

    // throw new Exception(json_encode($responsibilities), 1);


    $canDelete = $this->responsibilityService->hasAnyCode(
    $responsibilities,
    [
        'SUPER_ADMIN',
        'DOCUMENT_ADMIN',
    ]
);

 $canCancelOperation = $this->responsibilityService->hasAnyCode(
    $responsibilities,
    [
        'SUPER_ADMIN',
        'WORKFLOW_CANCELLER',
    ]
);

$capabilities['can_delete'] = $canDelete;

$capabilities['can_cancel_operation'] = $canCancelOperation;


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

  public function canIntervene(
    array $workflowContext,
    array $user,
    array $document
): bool {
    // L'étape doit être active.
    if (!($workflowContext['is_active'] ?? false)) {
        return false;
    }

    // throw new Exception(json_encode($this->hasExpectedRole($workflowContext, $user)), 1);


    // L'utilisateur doit posséder un rôle affecté à l'étape.
    if (!$this->hasExpectedRole($workflowContext, $user)) {
        return false;
    }



    // Vérifier les permissions de validation ou de signature.
    $requiredPermissions = array_values(array_intersect(
        ['validate', 'sign' , 'checkout'],
        $workflowContext['permissions_required'] ?? []
    ));


    // throw new Exception(json_encode($requiredPermissions), 1);


    if (empty($requiredPermissions)) {
        return false;
    }

    return $this->user_service_client->hasPermissions(
        $document,
        $user,
        $requiredPermissions,
        'any'
    );
}


/**
 * Récupère les actions autorisées pour l'étape courante du workflow.
 *
 * Structure attendue :
 * workflowContext.currentInstanceStep.workflow_step.config
 *
 * Exemple de config :
 * {
 *     "authorized_actions": [
 *         "define_purchase_type"
 *     ]
 * }
 *
 * @param array $workflowContext
 * @return array
 */
public function getAuthorizedActions(array $workflowContext): array
{
    $currentInstanceStep = $workflowContext['currentInstanceStep'] ?? [];

    $workflowStep = $currentInstanceStep['workflow_step'] ?? [];

    $config = $workflowStep['config'] ?? [];

    // Aucune configuration : aucune action supplémentaire autorisée.
    if (!is_array($config)) {
        return [];
    }

    $authorizedActions = $config['authorized_actions'] ?? [];

    // Garantit que le résultat est toujours un tableau.
    if (!is_array($authorizedActions)) {
        return [];
    }

    // Ne conserve que les identifiants d'action valides.
    return array_values(array_filter(
        $authorizedActions,
        function ($action) {
            return is_string($action) && trim($action) !== '';
        }
    ));
}

/**
 * Vérifie si une action est autorisée par la configuration
 * de l'étape courante du workflow.
 *
 * @param array $workflowContext
 * @param string $action
 * @return bool
 */
public function isActionAuthorized(
    array $workflowContext,
    string $action
): bool {
    if (trim($action) === '') {
        return false;
    }

    $authorizedActions = $this->getAuthorizedActions($workflowContext);

    return in_array($action, $authorizedActions, true);
}


public function canDefinePurchaseType(
    array $workflowContext,
    array $user,
    array $document
): bool {
    // 1. L'utilisateur doit pouvoir intervenir sur l'étape courante.
    if (!$this->canIntervene($workflowContext, $user, $document)) {
        return false;
    }
    


    // 2. L'étape courante doit autoriser cette action.
    if (!$this->isActionAuthorized(
        $workflowContext,
        'DEFINE_PURCHASE_TYPE'
    )) {
        return false;
    }



    // 3. L'utilisateur doit posséder la responsabilité PURCHASE_SERVICE.
    $responsibilities =
        $user['employeeContext']['responsibilities'] ?? [];

    $hasPurchaseServiceResponsibility =
        $this->responsibilityService->hasAnyCode(
            $responsibilities,
            ['PURCHASE_SERVICE']
        );

    // throw new Exception(json_encode($responsibilities), 1);
    

    if (!$hasPurchaseServiceResponsibility) {
        return false;
    }

    // throw new Exception(json_encode("ok"), 1);


    // 4. Le type d'achat ne doit pas déjà être défini.
    // $purchaseType = data_get(
    //     $document,
    //     'purchase_request.purchase_type'
    // );

    // if (!empty($purchaseType)) {
    //     return false;
    // }

    return true;
}



/**
 * Vérifie si l'utilisateur possède un rôle affecté à l'étape courante.
 *
 * @param array $workflowContext
 * @param array $user
 * @return bool
 */
private function hasExpectedRole(
    array $workflowContext,
    array $user
): bool {
    $currentInstanceStep = $workflowContext['currentInstanceStep'] ?? null;




    if (empty($currentInstanceStep)) {
        return false;
    }

    // Récupérer les rôles de l'utilisateur connecté.
    $userRoleIds = $user['role_ids'] ?? $user['role_id'] ?? [];

    // throw new Exception(json_encode($workflowContext), 1);


    if (empty($userRoleIds) && !empty($user['role_id'])) {
        $userRoleIds = [$user['role_id']];
    }

    if (empty($userRoleIds)) {
        return false;
    }

    // Récupérer les affectations de l'étape courante.
    $assignments = $currentInstanceStep['assignments'] ?? [];

    if (empty($assignments)) {
        return false;
    }

    // Extraire les rôles affectés à cette étape.
    $assignedRoleIds = array_map(
        function ($assignment) {
            return isset($assignment['role_id'])
                ? (int) $assignment['role_id']
                : null;
        },
        $assignments
    );

    $userRoleIds = array_map('intval', $userRoleIds);
    $assignedRoleIds = array_filter($assignedRoleIds);

    // Vérifier si l'utilisateur possède au moins un rôle attendu.
    return !empty(array_intersect(
        $userRoleIds,
        $assignedRoleIds
    ));
}


}