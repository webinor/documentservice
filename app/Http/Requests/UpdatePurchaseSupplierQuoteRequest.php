<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseSupplierQuoteRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à modifier un devis.
     *
     * Les autorisations métier doivent être gérées par les
     * middlewares ou les Policies de l'application.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'un devis.
     *
     * Tous les champs sont facultatifs afin de permettre
     * la complétion progressive du devis.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Fournisseur associé au devis.
            'supplier_id' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],

            // Référence du devis fournisseur.
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            

            // Montant du devis.
            'amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
                'max:9999999999999999.99',
            ],

          'quote_date' => [
    'sometimes',
    'nullable',
    'date_format:Y-m-d',
    'before_or_equal:today',
],

            'delivery_days' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
                'max:100',
            ],

            

            // Commentaire facultatif.
            'comment' => [
                'sometimes',
                'nullable',
                'string',
            ],

            // Raison pour laquelle on choisit ce devis.
            'selection_reason' => [
                'sometimes',
                'nullable',
                'string',
            ],

            

            // Fichier justificatif du devis.
            'attachment' => [
                'sometimes',
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],
        ];
    }

    /**
     * Vérifie qu'au moins un champ modifiable est fourni.
     *
     * @param mixed $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $allowedFields = [
                'supplier_id',
                'reference',
                'amount',
                'comment',
                'attachment',
                'selection_reason',
                'quote_date',
                'delivery_days'
            ];

            $submittedFields = array_keys($this->all());

            $hasAllowedField = count(
                array_intersect($allowedFields, $submittedFields)
            ) > 0;

            // Un fichier transmis en multipart/form-data peut être
            // absent de all() selon la version et le traitement.
            if (!$hasAllowedField && !$this->hasFile('attachment')) {
                $validator->errors()->add(
                    'quote',
                    'Au moins un champ à modifier doit être fourni.'
                );
            }
        });
    }

    /**
     * Messages de validation personnalisés.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'supplier_id.integer' =>
                'Le fournisseur doit être un identifiant valide.',

            'supplier_id.min' =>
                'L’identifiant du fournisseur est invalide.',

            'reference.string' =>
                'La référence doit être une chaîne de caractères.',

            'reference.max' =>
                'La référence ne peut pas dépasser 255 caractères.',

            'amount.numeric' =>
                'Le montant doit être numérique.',

            'amount.min' =>
                'Le montant doit être supérieur à zéro lorsqu’il est renseigné.',

            'amount.max' =>
                'Le montant dépasse la limite autorisée.',

            'comment.string' =>
                'Le commentaire doit être une chaîne de caractères.',

            'attachment.file' =>
                'Le justificatif doit être un fichier valide.',

            'attachment.mimes' =>
                'Le justificatif doit être au format PDF, JPG ou PNG.',

            'attachment.max' =>
                'Le justificatif ne doit pas dépasser 10 Mo.',
        ];
    }
}