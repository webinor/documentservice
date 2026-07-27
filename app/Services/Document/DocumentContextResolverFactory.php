<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;
use App\Services\Document\Contracts\DocumentContextResolver;
use App\Services\Document\Resolvers\DefaultCapabilitiesResolver;
use App\Services\Document\Resolvers\DefaultContextResolver;
use App\Services\Document\Resolvers\MissionCapabilitiesResolver;
use App\Services\Document\Resolvers\RegularizationCapabilitiesResolver;
use App\Services\Document\Resolvers\TaxiPaperContextResolver;

class DocumentContextResolverFactory
{
    public static function make(
         $document
    ): DocumentContextResolver {

        switch ($document['document_type']['slug']) {

            case "mission":

            case "fiche-a-regulariser":

            case "papier-taxi":
                return app(TaxiPaperContextResolver::class);

            default:
                return app(DefaultContextResolver::class);
        }
    }
}