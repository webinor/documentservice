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

    // throw new \Exception(json_encode($workflowContext), 1);


    $can_add_items = false;
     

        if ($document['regularization_sheet']['regularization_type'] == "INTERNAL") {
           
                   $can_add_items = in_array(
                "ADD_REGULARIZATION_ITEM",$workflowContext["business_actions"] ?? []
            ) && $document['actor_id'] == $user['employee_id'];
        
        }

        if ($document['regularization_sheet']['regularization_type'] == "ASSISTANCE") {
           
                   $can_add_items = in_array(
                "ADD_REGULARIZATION_ITEM",$workflowContext["business_actions"] ?? []
            ) ;
        
        }


        $actions["can_add_items"] = $can_add_items;

        



        return [

            "document_capabilities" => [

                "can_download" => true,

            ],

            "business_capabilities" => $actions

        ];

    }
}