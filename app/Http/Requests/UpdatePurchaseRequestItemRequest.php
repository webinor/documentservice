<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequestItemRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     *
     * L'autorisation métier peut être affinée selon les permissions
     * et les responsabilités de l'utilisateur connecté.
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
     * Les champs sont parfois optionnels pour permettre une mise à jour
     * partielle de l'article.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'designation' => 'sometimes|required|string|max:255',

            'requested_quantity' => 'sometimes|required|numeric|min:0.01',

            'approved_quantity' => 'sometimes|nullable|numeric|min:0',

            'unit_price' => 'sometimes|nullable|numeric|min:0',

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
            'designation.required' => 'La désignation de l’article est obligatoire.',
            'designation.string' => 'La désignation doit être une chaîne de caractères.',
            'designation.max' => 'La désignation ne peut pas dépasser 255 caractères.',

            'requested_quantity.required' => 'La quantité demandée est obligatoire.',
            'requested_quantity.numeric' => 'La quantité demandée doit être numérique.',
            'requested_quantity.min' => 'La quantité demandée doit être supérieure à zéro.',

            'approved_quantity.numeric' => 'La quantité approuvée doit être numérique.',
            'approved_quantity.min' => 'La quantité approuvée ne peut pas être négative.',

            'unit_price.numeric' => 'Le prix unitaire doit être numérique.',
            'unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',

            'expected_date.date' => 'La date prévue est invalide.',
        ];
    }
}