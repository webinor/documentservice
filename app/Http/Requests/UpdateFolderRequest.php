<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation pour la mise à jour d'un dossier.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'folder_id' => 'integer|exists:folders,id',
            'departmentIds' => 'required|array',
            'departmentIds.*' => 'integer',
            'notify_allowed_user' => 'required|boolean', // ✅ nouveau champ validé

        ];
    }

    /**
     * Messages personnalisés (facultatif).
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du dossier est obligatoire.',
            'name.string' => 'Le nom du dossier doit être une chaîne de caractères.',
            'departmentIds.array' => 'Les départements doivent être envoyés sous forme de tableau.',
            'departmentIds.*.exists' => 'Certains départements sélectionnés n’existent pas.',
        ];
    }
}
