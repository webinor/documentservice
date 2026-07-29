<?php

namespace App\Services\Document;

use App\Models\Misc\Document;
use App\Services\Document\Contracts\DocumentCapabilitiesResolver;
use App\Services\Document\Contracts\DocumentContextResolver;
use App\Services\Document\Resolvers\DefaultCapabilitiesResolver;
use App\Services\Document\Resolvers\DefaultContextResolver;
use App\Services\Document\Resolvers\MissionCapabilitiesResolver;
use App\Services\Document\Resolvers\RegularizationCapabilitiesResolver;
use App\Services\Document\Resolvers\RegularizationSheetContextResolver;
use App\Services\Document\Resolvers\TaxiPaperContextResolver;

class DocumentContextResolverFactory
{
    public static function make(
         $document
    ): DocumentContextResolver {

    $type = $document['document_type']['slug'];

        switch ($type) {

            case "mission":

            throw new \Exception("Aucun DocumentContextResolverFactory pour $type", 1);

            case "fiche-a-regulariser":

                            return app(RegularizationSheetContextResolver::class);

                
            case "papier-taxi":
                // throw new \Exception($document['document_type']['slug'], 1);

                return app(TaxiPaperContextResolver::class);

            default:
                return app(DefaultContextResolver::class);
        }
    }
}