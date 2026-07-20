<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;
use App\Services\Document\Resolvers\DefaultCapabilitiesResolver;
use App\Services\Document\Resolvers\MissionCapabilitiesResolver;
use App\Services\Document\Resolvers\RegularizationCapabilitiesResolver;
use App\Services\Document\Resolvers\TaxiPaperCapabilitiesResolver;

class DocumentCapabilitiesResolverFactory
{
    public static function make(
         $document
    ): DocumentCapabilitiesResolver {

        switch ($document['document_type']['slug']) {

            case "mission":
                // return app(MissionCapabilitiesResolver::class);

            case "fiche-a-regulariser":
                return app(RegularizationCapabilitiesResolver::class);

            case "taxi-paper":
                // return app(TaxiPaperCapabilitiesResolver::class);

            default:
                return app(DefaultCapabilitiesResolver::class);
        }
    }
}