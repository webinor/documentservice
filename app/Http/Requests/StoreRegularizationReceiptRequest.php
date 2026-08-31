<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegularizationReceiptRequest extends FormRequest
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
            'receipt' => [
                'required',
                'file',
                // 'mimes:pdf,jpg,jpeg,png',
                'mimes:pdf',
                'max:10240', // 10 MB
            ],
        ];
    }
}