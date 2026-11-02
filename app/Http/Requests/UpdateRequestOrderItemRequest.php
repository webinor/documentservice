<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestOrderItemRequest extends FormRequest
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
            'designation' => 'sometimes|nullable|string|max:255',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit_price' => 'sometimes|required|numeric|min:0',
        ];
    }
}