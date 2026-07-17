<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegularizationItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [

            'designation' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'quantity' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'unit_price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'comment' => [
                'nullable',
                'string',
            ],

            'receipt' => [
                'nullable',
                'file',
                'max:10240',
            ],

        ];
    }
}