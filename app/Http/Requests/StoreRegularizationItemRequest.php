<?php

namespace App\Http\Requests;

use App\Enums\AssistanceMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegularizationItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'document' => [
                'required',
                'exists:documents,id',
            ],

           

'assistance_mode' => [
    'nullable',
    Rule::in(AssistanceMode::values()),
],

 'case_number' => [
            Rule::requiredIf(function () {

                return in_array(
                    $this->assistance_mode,
                    [
                        AssistanceMode::ASSISTANCE_NORMAL,
                        AssistanceMode::ASSISTANCE_URGENT,
                    ]
                );

            }),
            'string',
            'max:100',
        ],

            'designation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'unit_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'comment' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'receipt' => [
                'nullable',
                'file',
                'max:10240', // 10 Mo
            ],
        ];
    }

    public function messages()
    {
        return [
            'document.required' => 'Le document est obligatoire.',
            'document.exists' => 'Le document est introuvable.',

            'designation.max' => 'La désignation est trop longue.',

            'quantity.numeric' => 'La quantité doit être numérique.',
            'quantity.min' => 'La quantité doit être supérieure ou égale à zéro.',

            'unit_price.numeric' => 'Le prix unitaire doit être numérique.',
            'unit_price.min' => 'Le prix unitaire doit être supérieur ou égal à zéro.',

            'comment.max' => 'Le commentaire est trop long.',

            'receipt.file' => 'Le justificatif doit être un fichier.',
            'receipt.max' => 'Le justificatif ne doit pas dépasser 10 Mo.',
        ];
    }
}