<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequestRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette modification.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Règles de validation des données reçues.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // Le client peut être défini ou retiré de la demande d'achat.
            // S'il est renseigné, il doit exister dans la table clients.
            'client_id' => 'sometimes|nullable|integer',
            'type' => 'sometimes|nullable|string',
        ];
    }
}