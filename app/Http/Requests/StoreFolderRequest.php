<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFolderRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la création d'un dossier.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departmentIds' => 'required|array',
            'departmentIds.*' => 'integer',
            'parent_id' => 'nullable|integer',
            'notify_allowed_user' => 'required|boolean', // ✅ nouveau champ validé

        ];
    }

    /**
     * Messages d’erreur personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du dossier est obligatoire.',
            'name.string' => 'Le nom du dossier doit être une chaîne de caractères.',
            'departmentIds.array' => 'Les départements doivent être envoyés sous forme de tableau.',
            'departmentIds.*.exists' => 'Un des départements sélectionnés est invalide.',
        ];
    }
}
