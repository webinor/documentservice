<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMissionExpenseRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'document' => ['required', 'exists:documents,id'],

            'expense_category_id' => [
                'nullable',
                'exists:expense_categories,id'
            ],

            'amount' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'expense_date' => [
                'nullable',
                'date'
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ];
    }

    public function messages()
    {
        return [
            'mission_id.required' => 'La mission est obligatoire.',
            'mission_id.exists' => 'Mission introuvable.',

            'expense_category_id.exists' => 'Catégorie invalide.',

            'amount.numeric' => 'Le montant doit être numérique.',
            'amount.min' => 'Le montant doit être positif.',
        ];
    }
}