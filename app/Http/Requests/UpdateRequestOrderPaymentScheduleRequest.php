<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestOrderPaymentScheduleRequest extends FormRequest
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
            'paid_amount' => 'sometimes|required|numeric|min:0',
            'status' => [
                'sometimes',
                'required',
                Rule::in([
                    'PLANNED',
                    'DUE',
                    'PARTIALLY_PAID',
                    'PAID',
                    'CANCELLED',
                ])
            ]
        ];
    }
}
