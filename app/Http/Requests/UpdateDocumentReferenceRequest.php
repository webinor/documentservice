<?php

namespace App\Http\Requests;

use App\Models\DocumentReference;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentReferenceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $documentReference = $this->route('document_reference')
            ?? $this->route('documentReference');

        $referenceId = is_object($documentReference)
            ? $documentReference->id
            : $documentReference;

        return [

            'document_reference_type_id' => [
                'required',
                'exists:document_reference_types,id',
            ],

            'reference' => [
                'required',
                'string',
                'regex:/^[0-9]{1,4}$/',

                function ($attribute, $value, $fail) use ($referenceId) {

                    /*
                    |--------------------------------------------------------------------------
                    | Référence saisie
                    |--------------------------------------------------------------------------
                    |
                    | Exemple :
                    | 0203
                    |
                    |--------------------------------------------------------------------------
                    | Référence réellement stockée
                    |--------------------------------------------------------------------------
                    |
                    | 260203
                    |
                    */

                    $fullReference = date('y') . str_pad(
                        $value,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );

                    $exists = DocumentReference::where(
                        'reference',
                        $fullReference
                    )
                    ->where('id', '!=', $referenceId)
                    ->exists();

                    if ($exists) {
                        $fail('Cette référence est déjà utilisée.');
                    }
                },
            ],

            'attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages()
    {
        return [

            'document_reference_type_id.required' =>
                'Le type de référence est obligatoire.',

            'document_reference_type_id.exists' =>
                'Le type de référence est invalide.',

            'reference.required' =>
                'La référence est obligatoire.',

            'reference.regex' =>
                'La référence doit contenir uniquement des chiffres (1 à 4 chiffres).',

            'attachment.file' =>
                'Le fichier est invalide.',

            'attachment.max' =>
                'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}