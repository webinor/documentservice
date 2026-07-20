<?php

namespace App\Services\Document\Resolvers;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;

class RegularizationCapabilitiesResolver
implements DocumentCapabilitiesResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array {

        $actions = [];

        /*
        |--------------------------------------------------------------------------
        | Etape métier
        |--------------------------------------------------------------------------
        */

        // return [$document['actor_id'] == $user['employee_id']];

        $can_add_items = in_array(
                "ADD_REGULARIZATION_ITEM",
                $workflowContext["business_actions"] ?? []
            ) && $document['actor_id'] == $user['employee_id'];

        // $currentStep = optional($document->workflowInstance)->currentStep;


        $actions["can_add_items"] = true;
        $actions["can_add_items"] = $can_add_items;



        return [

            "document_capabilities" => [

                "can_download" => true,

            ],

            "business_capabilities" => $actions

        ];

    }
}