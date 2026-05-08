<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionExpenseRequest extends FormRequest
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
            'string'
        ],
    ];

    
    }
}
