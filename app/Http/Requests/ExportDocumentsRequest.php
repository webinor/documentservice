<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportDocumentsRequest extends FormRequest
{
    /**
     * Autorisation de la requête.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Règles de validation.
     */
    public function rules()
    {
        return [
            'document_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'document_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],

            'document_type' => [
                'required',
                'string',
                'max:100',
            ],

            'columns' => [
                'required',
                'array',
                'min:1',
            ],

            'columns.*' => [
                'required',
                'string',
                'max:100',
            ],

            /*
             * Données calculées par le workflow-service.
             *
             * Elles permettent notamment d'injecter dans l'export
             * des informations qui n'appartiennent pas au document-service,
             * comme le statut workflow.
             */
            'workflow_metadata' => [
                'nullable',
                'array',
            ],

            'workflow_metadata.*' => [
                'nullable',
                'array',
            ],
        ];
    }
}