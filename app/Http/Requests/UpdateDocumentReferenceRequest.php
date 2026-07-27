<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentReferenceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $referenceId = $this->route('document_reference')
            ?? $this->route('documentReference')->id;

            // throw new \Exception("$referenceId", 1);
            

        return [
            'document_reference_type_id' => [
                'required',
                'exists:document_reference_types,id',
            ],

            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('document_references', 'reference')
                    ->ignore($referenceId),
            ],

            'attachment' => [
                'nullable',
                'file',
                'max:10240', // 10 Mo
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
            'document_reference_type_id.required' => 'Le type de référence est obligatoire.',
            'document_reference_type_id.exists'   => 'Le type de référence est invalide.',

            'reference.required' => 'La référence est obligatoire.',
            'reference.unique'   => 'Cette référence existe déjà.',

            'attachment.file' => 'Le fichier est invalide.',
            'attachment.max'  => 'Le fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}