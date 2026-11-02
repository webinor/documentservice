<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestOrderRequest extends FormRequest
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
            'purchase_request_id' => [
                'required',
                'integer',
                'exists:purchase_requests,id',
            ],

            'supplier_quote_id' => [
                'required',
                'integer',
                'exists:purchase_supplier_quotes,id',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'purchase_request_id.required' => 'La demande d\'achat est obligatoire.',
            'purchase_request_id.exists' => 'La demande d\'achat sélectionnée est introuvable.',

            'supplier_quote_id.required' => 'Le devis fournisseur est obligatoire.',
            'supplier_quote_id.exists' => 'Le devis fournisseur sélectionné est introuvable.',
        ];
    }
}