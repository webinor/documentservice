<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRegularizationReceiptItemsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => [
                'array',
            ],

            'items.*.item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:regularization_items,id',
            ],

            'items.*.allocated_amount' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }
}