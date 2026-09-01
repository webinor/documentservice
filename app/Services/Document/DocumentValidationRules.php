<?php

namespace App\Services\Document;

use Illuminate\Validation\Rule;

class DocumentValidationRules
{
    public static function for(string $slug, $request): array
    {
        $baseRules = [
            'titre' => 'required|string',
            "document_type_id" => "required|exists:document_types,id",
            
        ];

        $rulesByType = [

            'papier-taxi' => [
                'libelles' => 'required|array|min:1',
                'libelles.*.libelle' => 'required|string',
                'libelles.*.montant' => 'required|numeric',
                'beneficiaire' => 'required|numeric',
            ],

            'note-de-frais' => [
                'montant' => 'required|numeric',
                'beneficiaire' => 'required|numeric',
            ],

            'fiche-a-regulariser' => [
                'beneficiaire' => 'required|numeric',

                'regularization_type' => 'nullable|string',
                'assistance_mode' => 'nullable|string',
                'case_number' => 'nullable|string',

                'libelles' => 'required|array|min:1',
                'libelles.*.libelle' => 'required|string',
                'libelles.*.quantite' => 'required|numeric',
                'libelles.*.montant' => 'required|numeric',
            ],

            'demande-d-absence' => [
                'beneficiaire' => 'required|numeric',

                'dateDepart' => 'required|date',
                'dateRetour' => 'required|date',
                "commentaire" => "nullable|string",


                'motif' => [
                    Rule::requiredIf(
                        $request->input('titre') === 'PERMISSION'
                    ),
                    'nullable',
                    'string',
                ],

                'type_conge' => [
                    Rule::requiredIf(
                        $request->input('titre') === 'CONGE'
                    ),
                    'nullable',
                    'string',
                ],

                'heureDepart' => [
                    Rule::requiredIf(
                        $request->input('titre') === 'PERMISSION'
                    ),
                    'nullable',
                    'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                ],

                'heureRetour' => [
                    Rule::requiredIf(
                        $request->input('titre') === 'PERMISSION'
                    ),
                    'nullable',
                    'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
                ],
            ],
        ];

        return array_merge(
            $baseRules,
            $rulesByType[$slug] ?? []
        );
    }
}