<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequestItemRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Règles de validation des champs modifiables.
     *
     * Les champs peuvent être optionnels pour permettre une mise à jour
     * partielle de l'article.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'designation' => 'sometimes|required|string|max:255',

            'requested_quantity' => 'sometimes|required|integer|min:1',

            'approved_quantity' => 'sometimes|nullable|integer|min:0',

            'requested_amount' => 'sometimes|nullable|numeric|min:0',

            'approved_amount' => 'sometimes|nullable|numeric|min:0',

            'specification' => 'sometimes|nullable|string',

            'comment' => 'sometimes|nullable|string',

            'urgency' => 'sometimes|nullable|string|max:100',

            'expected_date' => 'sometimes|nullable|date',

            'notice' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Messages de validation personnalisés.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'designation.required' =>
                'La désignation de l’article est obligatoire.',
            'designation.string' =>
                'La désignation doit être une chaîne de caractères.',
            'designation.max' =>
                'La désignation ne peut pas dépasser 255 caractères.',

            'requested_quantity.required' =>
                'La quantité demandée est obligatoire.',
            'requested_quantity.integer' =>
                'La quantité demandée doit être un nombre entier.',
            'requested_quantity.min' =>
                'La quantité demandée doit être supérieure à zéro.',

            'approved_quantity.integer' =>
                'La quantité approuvée doit être un nombre entier.',
            'approved_quantity.min' =>
                'La quantité approuvée ne peut pas être négative.',

            'requested_amount.numeric' =>
                'Le montant demandé doit être numérique.',
            'requested_amount.min' =>
                'Le montant demandé ne peut pas être négatif.',

            'approved_amount.numeric' =>
                'Le montant approuvé doit être numérique.',
            'approved_amount.min' =>
                'Le montant approuvé ne peut pas être négatif.',

            'expected_date.date' =>
                'La date prévue est invalide.',
        ];
    }
}