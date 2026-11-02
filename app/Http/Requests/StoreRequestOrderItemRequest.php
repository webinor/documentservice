<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestOrderItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'designation' => 'nullable|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
        ];
    }
}