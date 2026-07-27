<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReferenceRequest extends FormRequest
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

        "documentId" => [
            "required",
            "exists:documents,uuid"
        ],

        "document_reference_type_id" => [
            "required",
            "exists:document_reference_types,id"
        ],


        "reference" => [
            "required",
            "string",
            "max:255"
        ],


        "reference_type_code" => [
            "nullable",
            "string"
        ],


        "metadata" => [
            "nullable",
            "array"
        ],


        "attachment" => [
            "nullable",
            "file"
        ]

    ];
}
}
