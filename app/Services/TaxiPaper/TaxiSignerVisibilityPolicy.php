<?php

namespace App\Services\TaxiPaper;

use App\Contracts\SignerVisibilityPolicy;

class TaxiSignerVisibilityPolicy implements SignerVisibilityPolicy
{
    public function isVisible(array $participant): bool
    {
        // Le participant doit être validé
        if (($participant['status'] ?? null) !== "APPROVED") {
            return false;
        }


        $sourceType = $participant['source_type'] ?? null;
        $sourceValue = $participant['source_value'] ?? null;


        // Récupération sécurisée des responsabilités
        $responsibilities = $participant['user']['responsibilities'] ?? [];


        // Si jamais responsibilities arrive en null
        if (!is_array($responsibilities)) {
            $responsibilities = [];
        }

        if (sizeof($responsibilities)>0) {
            
        throw new \Exception(json_encode($responsibilities), 1);

        
        }


        /**
         * Cas propriétaire :
         * visible uniquement si l'utilisateur possède une responsabilité
         * permettant la signature
         */
        if (
            $sourceType === "OWNER" &&
            !empty(array_intersect(
                $responsibilities,
                [
                    "SIGNATORY",
                    "HEAD_OF_DEPARTMENT"
                ]
            ))
        ) {
        // throw new \Exception(json_encode($participant['user']['responsibilities']), 1);

            return true;
        }


        /**
         * Cas basé sur une valeur métier du participant
         */
        if (in_array($sourceValue, [
            'DIRECT_MANAGER',
            'HEAD_OF_DEPARTMENT',
            'SIGNATORY',
        ], true)) {
            return true;
        }


        return false;
    }
}