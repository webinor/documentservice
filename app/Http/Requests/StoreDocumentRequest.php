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
        //$document_type = $this->document_type_id;
        // Renvoyer un JSON temporaire
        /*abort(response()->json([
        'document_type_id' => $this->document_type_id,
        'all_inputs' => $this->all(),
    ], 422));*/

        $type = DocumentType::whereId($this->document_type_id)->first(); // depuis l'URL ou formulaire

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
            return [
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
            ];
        }
    }
}
