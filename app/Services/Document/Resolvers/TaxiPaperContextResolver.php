<?php

namespace App\Services\Document\Resolvers;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentContextResolver;

class TaxiPaperContextResolver
implements DocumentContextResolver
{
    public function resolve(
         $document,
        $workflowContext,
        array $user
    ): array {

    // return
        $actions = [];

        /*
        |--------------------------------------------------------------------------
        | Etape métier
        |--------------------------------------------------------------------------
        */


        $signature = sizeof($workflowContext["signatures"]) > 0 ? $workflowContext["signatures"][0] : null;
        // $signature = ($workflowContext["signatures"]) > 0 ? $workflowContext["signatures"] : null;


 
        



        return [

            "signature" => $signature


        ];

    }
}