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

            /*
             * DEMANDE D'ACHAT
             */
            'achat' => [
                // 'titre' => 'required|string',
                'description' => 'nullable|string',
                
                'category' => [
                    'nullable',
                    // 'string',
                    Rule::in([
                        'IT_EQUIPMENT',
                        'SOFTWARE',
                        'OFFICE_SUPPLY',
                        'FURNITURE',
                        'VEHICLE',
                        'TELECOM',
                        'SERVICE',
                        'OTHER',
                    ]),
                ],

                'type' => [
    'sometimes',
    'required',
    'in:INTERNAL,EXTERNAL',
],

                'destination_service_id' => 'nullable|numeric',

                'priority' => [
                    'nullable',
                    'string',
                    Rule::in([
                        'LOW',
                        'MEDIUM',
                        'HIGH',
                        'CRITICAL',
                    ]),
                ],

                'libelles' => 'required|array|min:1',
                
                'libelles.*.libelle' => 'required|string',
                'libelles.*.quantite' => 'required|numeric|min:1',
                'libelles.*.montant' => 'sometimes|numeric|min:1',
                'libelles.*.specification' => 'nullable|string',

                'attachments' => 'nullable',
            ],

            /*
 * MISSION
 */
'mission' => [
    'destination' => 'required|string',
    
    'scope' => [
        'required',
        Rule::in([
            'LOCAL',
            'NATIONAL',
            'INTERNATIONAL',
        ]),
    ],

    'beneficiaire' => 'required|numeric',

    'autre_beneficiaire' => 'nullable|numeric',

    'actor_type' => [
        'required',
        Rule::in([
            'me',
            'other',
        ]),
    ],

    'actor_collaborator' => 'nullable|numeric',

    /*
     * Dépenses prévisionnelles
     */
    'expenses' => 'required|array|min:1',

    'expenses.*.expense_category_id' => 'required|exists:expense_categories,id',

    'expenses.*.amount' => 'required|numeric|min:0',

    'expenses.*.comment' => 'nullable|string',

    'expenses.*.type' => [
        'required',
        Rule::in([
            'PREVISIONNELLE',
        ]),
    ],

    'expenses.*.planned_quantity' => 'nullable|numeric|min:1',

    /*
     * Dates et heures prévisionnelles
     */
    'departure_date_base_planned' => 'required|date',
    'departure_time_base_planned' => 'required|date_format:H:i',

    'arrival_date_site_planned' => 'required|date',
    'arrival_time_site_planned' => 'required|date_format:H:i',

    'departure_date_site_planned' => 'required|date',
    'departure_time_site_planned' => 'required|date_format:H:i',

    'arrival_date_base_planned' => 'required|date',
    'arrival_time_base_planned' => 'required|date_format:H:i',
],
        ];

        return array_merge(
            $baseRules,
            $rulesByType[$slug] ?? []
        );
    }
}