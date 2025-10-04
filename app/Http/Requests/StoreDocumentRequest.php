<?php

namespace App\Http\Requests;

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
        return [
            'titre'=>'required',
            'prestataire'=>'required',
            'reference_fournisseur'=>'required',
            'reference_engagement'=>'nullable',
            'dateDepot'=>'required|before:now',
            'montant'=>'required',
            'linkedDocument'=>'nullable|exists:documents,reference',
            'document_type_id' => 'required|exists:document_types,id',
            'departement' => 'nullable',
            'facture' => 'required|file|max:10240', // fichiers
        ];
    }
}
