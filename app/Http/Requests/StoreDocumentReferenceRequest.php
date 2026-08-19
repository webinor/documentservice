<?php

namespace App\Http\Requests;

use App\Models\DocumentReference;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReferenceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [

            'documentId' => [
                'required',
                'exists:documents,uuid',
            ],

            'document_reference_type_id' => [
                'required',
                'exists:document_reference_types,id',
            ],

            'reference' => [
                'required',
                'string',
                'regex:/^[0-9]{1,4}$/',

                function ($attribute, $value, $fail) {

                    // Référence saisie par l'utilisateur :
                    // 0203
                    //
                    // Référence réellement stockée :
                    // 260203

                    $fullReference = date('y') . str_pad(
                        $value,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );

                    if (
                        DocumentReference::where(
                            'reference',
                            $fullReference
                        )->exists()
                    ) {
                        $fail('Cette référence existe déjà.');
                    }
                },
            ],

            'reference_type_code' => [
                'nullable',
                'string',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],

            'attachment' => [
                'nullable',
                'file',
                'max:10240',
            ],
        ];
    }

    public function messages()
    {
        return [

            'documentId.required' =>
                'Le document est obligatoire.',

            'documentId.exists' =>
                'Le document est invalide.',

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