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
    "motif" => "required|string",
    "trajets" => "required|array|min:1",
    "trajets.*.trajet" => "required|string",
    "trajets.*.montant" => "required|numeric",
    "beneficiaire" => "required|numeric",
];

$feeNoteFields = [
    "motif" => "required|string",
    "montant" => "required|numeric",
    "beneficiaire" => "required|numeric",
];

$absenceRequestFields = [

    "motif"        => "required|string",
    "beneficiaire" => "required|numeric",

    "dateDepart"   => "required|date",
    "heureDepart"  => [
        "required",
        "regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/"
    ],

    "dateRetour"   => "required|date",
    "heureRetour"  => [
        "required",
        "regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/"
    ],


];



$rulesByType = [
    'facture-fournisseur-medical' => [$baseRules, $invoiceFields],
    'facture-fournisseur-informatique' => [$baseRules, $invoiceFields],
    'facture-note-honoraire' => [$baseRules, $invoiceFields],
    'CREDIT_NOTE' => [$baseRules, $invoiceFields], // ✔️ mêmes champs !
    'papier-taxi' => [$baseRules, $taxiFields],
    'note-de-frais' => [$baseRules, $feeNoteFields],
    'demande-d-absence' => [$baseRules, $absenceRequestFields],
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


        /*    return [
                "titre" => "required",
                "prestataire" => "required",
                "reference_fournisseur" => "required",
                "reference_engagement" => "nullable",
                "dateDepot" => "required|before:now",
                "montant" => "required",
                "linkedDocument" => "nullable|exists:documents,reference",
                "document_type_id" => "required|exists:document_types,id",
                "departement" => "nullable",
                "facture" => "required|file|max:10240", // fichiers
            ];*/
        }
    }
}
