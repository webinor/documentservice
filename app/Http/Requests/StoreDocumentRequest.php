<?php

namespace App\Http\Requests;

use App\Models\Misc\DocumentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $type = DocumentType::findOrFail($this->document_type_id);

        // 🔥 Mapping dynamique : chaque type a ses règles
        $baseRules = [
            "titre" => "required|string",
            "document_type_id" => "required|exists:document_types,id",
            "departement" => "nullable",
        ];

        $invoiceFields = [
            "prestataire" => "required|string",
            "reference_fournisseur" => "required|string",
            "dateDepot" => "required|date|before:now",
            "montant" => "required|numeric",
        ];

        $taxiFields = [
            // "motif" => "required|string",
            "trajets" => "required|array|min:1",
            "trajets.*.trajet" => "required|string",
            "trajets.*.montant" => "required|numeric",
            "beneficiaire" => "required|numeric",
            //"montant" => "required|numeric",
        ];

        $feeNoteFields = [
            "motif" => "required|string",
            "montant" => "required|numeric",
            "beneficiaire" => "required|numeric",
        ];

        $absenceRequestFields = [
            "motif" => "required|string",
            "beneficiaire" => "required|numeric",

            "dateDepart" => "required|date",
            "heureDepart" => [
                "required",
                "regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/",
            ],

            "dateRetour" => "required|date",
            "heureRetour" => [
                "required",
                "regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/",
            ],
        ];

        $missionFields = [
            // Infos mission
            "destination" => "required|string",
            "title" => "nullable|string",

            // Budget
            "estimated_budget" => "nullable|numeric|min:0",
            "advance_amount" => "nullable|numeric|min:0",

            // Mission spéciale
            "mission_special" => "nullable|in:1,0",

            // Acteur (missionnaire)
            "actor_type" => "required|in:me,collaborator,external",

            "scope" => "required|in:LOCAL,NATIONAL,INTERNATIONAL",

            // Collaborateur interne
            "actor_collaborator" =>
                "required_if:actor_type,collaborator|nullable",

            // Prestataire externe
            "actor_external" => "required_if:actor_type,external|nullable",

            /**
             * ==========================================
             * 🧭 BASE (départ/retour du siège)
             * ==========================================
             */

            "departure_date_base_planned" => "required|date",
            "departure_time_base_planned" => "required",

            "arrival_date_base_planned" => "required|date",
            "arrival_time_base_planned" => "required",

            "departure_date_base_actual" => "nullable|date",
            "departure_time_base_actual" => "nullable",

            "arrival_date_base_actual" => "nullable|date",
            "arrival_time_base_actual" => "nullable",

            /**
             * ==========================================
             * 🏗 SITE (départ/retour intervention)
             * ==========================================
             */

            "departure_date_site_planned" => "required|date",
            "departure_time_site_planned" => "required",

            "arrival_date_site_planned" => "required|date",
            "arrival_time_site_planned" => "required",

            "departure_date_site_actual" => "nullable|date",
            "departure_time_site_actual" => "nullable",

            "arrival_date_site_actual" => "nullable|date",
            "arrival_time_site_actual" => "nullable",

            /**
             * Dépenses
             */

            "expenses" => "nullable|string",
            "expenses.*.receipt" => "nullable|file|max:5120",
        ];

        $purchaseRequestFields = [
            /**
             * Informations générales
             */
            "title" => "required|string|max:255",

            "description" => "required|string",

            "destination_service_id" => "required|integer",

            "priority" => "required|in:LOW,MEDIUM,HIGH,CRITICAL",

            "category" => "required|in:IT_EQUIPMENT,SOFTWARE,OFFICE_SUPPLY,FURNITURE,VEHICLE,TELECOM,SERVICE,OTHER",

            /**
             * Articles demandés
             */
            "items" => "required|array|min:1",

            "items.*.designation" => "required|string|max:255",

            "items.*.quantity" => "required|integer|min:1",

            "items.*.specification" => "nullable|string",

            /**
             * Optionnel mais fortement recommandé
             */
            "items.*.estimated_unit_price" => "nullable|numeric|min:0",

            /**
             * Pièces jointes
             */
            "attachments" => "nullable|array",

            "attachments.*" => "file|max:10240",

            "estimated_amount" => 'nullable|numeric|min:0',
        ];

        $rulesByType = [
            "facture-fournisseur-medical" => [$baseRules, $invoiceFields],
            "facture-fournisseur-informatique" => [$baseRules, $invoiceFields],
            "facture-note-honoraire" => [$baseRules, $invoiceFields],
            "CREDIT_NOTE" => [$baseRules, $invoiceFields], // ✔️ mêmes champs !
            "papier-taxi" => [$baseRules, $taxiFields],
            "note-de-frais" => [$baseRules, $feeNoteFields],
            "demande-d-absence" => [$baseRules, $absenceRequestFields],
            // 🔥 AJOUT ICI
            "mission" => [$baseRules, $missionFields],
            "demande-achat" => [$baseRules, $purchaseRequestFields],
        ];

        //$type = DocumentType::whereId($this->document_type_id)->first(); // depuis l'URL ou formulaire

        if ($type->reception_mode == "AUTO_BY_ROLE") {
            /*$fields = DB::table('form_fields')
            ->where('document_type_id', $typeId)
            ->get();*/

            return [
                "titre" => "required",
                //'description'=>'required',
                //'dateReception'=>'required|before:now',
                //'expediteur'=>'required',
                //'linkedDocument'=>'nullable|exists:documents,reference',
                "destination" => "required|exists:folders,id",
                "document_type_id" => "required|exists:document_types,id",
                //'reference_expediteur' => 'required',
                "courrier" => "required|file|max:10240", // fichiers
            ];
        } elseif ($type->reception_mode == "WORKFLOW_DRIVEN") {
            $selected = $rulesByType[$type->slug] ?? [$baseRules];

            // Fusionne proprement les règles
            return array_merge(...$selected);
        }
    }
}
