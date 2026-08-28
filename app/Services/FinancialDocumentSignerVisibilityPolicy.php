<?php

namespace App\Services;

use App\Contracts\SignerVisibilityPolicy;

abstract class FinancialDocumentSignerVisibilityPolicy
    implements SignerVisibilityPolicy
{
    

    /**
     * Détermine si un participant doit apparaître comme signataire visible
     *
     * Règles métier :
     * - Le participant doit être approuvé.
     * - Un OWNER est visible uniquement s'il possède une responsabilité
     *   permettant la signature.
     * - Certaines sources métier peuvent directement donner un droit de signature.
     */
    public final function isVisible(array $participant, array $documentData = []): bool
    {

    



        /**
         * Un participant non approuvé ne doit jamais être proposé
         * comme signataire.
         */
        if (($participant['status'] ?? null) !== "APPROVED") {
            return false;
        }



        
           

        $creator = $participant['user'];

      

        /**
         * Type de source du participant.
         *
         * Exemple :
         * OWNER
         * DEPARTMENT
         * ROLE
         */
        $sourceType = $participant['source_type'] ?? null;


        /**
         * Valeur métier associée au participant.
         *
         * Exemple :
         * DIRECT_MANAGER
         * HEAD_OF_DEPARTMENT
         * SIGNATORY
         */
        $sourceValue = $participant['source_value'] ?? null;


        if (
            $sourceType === "OWNER" &&

            ($creator['employee_id'] ?? null) === ($documentData['creator_employee_id'] ?? null)


        ) {
            return false;
        }



        /**
         * Récupération des responsabilités utilisateur.
         *
         * Attention :
         * L'API User retourne une structure :
         *
         * [
         *    [
         *       "id" => 12,
         *       "code" => "SIGNATORY",
         *       "label" => "Signataire"
         *    ]
         * ]
         *
         * Or les règles workflow utilisent uniquement les codes.
         *
         * On transforme donc en :
         *
         * [
         *    "SIGNATORY"
         * ]
         */
        $responsibilities = collect(
            $participant['user']['responsibilities'] ?? []
        )
        ->pluck('code')
        ->toArray();



        /**
         * Cas 1 :
         *
         * Le participant est le propriétaire du document.
         *
         * Un OWNER n'est pas forcément signataire.
         * Il doit posséder une responsabilité permettant la signature.
         *
         * Exemple :
         *
         * Employé :
         * - Responsable département
         * - Signataire
         *
         * => Visible
         */
        if (
            $sourceType === "OWNER" &&

            (!empty(array_intersect(
                $responsibilities,
                [
                    "SIGNATORY",
                    "HEAD_OF_DEPARTMENT"
                ]
            ))   )


        ) {
            return true;
        }



        /**
         * Cas 2 :
         *
         * Certaines sources métier représentent directement
         * une personne habilitée à signer.
         *
         * Exemple :
         *
         * source_value = DIRECT_MANAGER
         *
         * Le manager direct doit être visible.
         */
        if (
            in_array(
                $sourceValue,
                [
                    'DIRECT_MANAGER',
                    'HEAD_OF_DEPARTMENT',
                    'SIGNATORY',
                ],
                true
            )
        ) {
            return true;
        }

        if ($creator && $sourceType === "OWNER" && $creator['employee_id'] != $documentData['actor_id']) {
            

            // throw new \Exception(json_encode($creator && $sourceType === "OWNER" && $creator['employee_id'] != $documentData['actor_id']), 1);
      
            return true;




        }





        /**
         * Par défaut :
         *
         * Le participant n'a aucune règle permettant
         * de le considérer comme signataire.
         */
          return $this->isSpecificRuleSatisfied(
            $participant,
            $documentData
        );
    }

    protected function isSpecificRuleSatisfied(
        array $participant,
        array $documentData
    ): bool {
        return false;
    }
}