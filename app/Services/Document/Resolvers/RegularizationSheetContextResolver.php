<?php

namespace App\Services\Document\Resolvers;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentContextResolver;

class RegularizationSheetContextResolver
implements DocumentContextResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array {

  
        /*
        |--------------------------------------------------------------------------
        | Etape métier
        |--------------------------------------------------------------------------
        */


        $signatures = (isset($workflowContext["signatures"]) && sizeof($workflowContext["signatures"]) > 0 )? $workflowContext["signatures"][0] : null;
        // $signature = ($workflowContext["signatures"]) > 0 ? $workflowContext["signatures"] : null;


 
        



        return [

            "signatures" => $signatures


        ];

    }
}