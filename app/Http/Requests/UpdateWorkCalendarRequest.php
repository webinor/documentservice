<?php

namespace App\Http\Requests;

use App\Models\WorkCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkCalendarRequest extends FormRequest
{
    /**
     * Autorisation de la requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation.
     */
    public function rules(): array
    {
        /** @var WorkCalendar|null $workCalendar */
        $workCalendar = $this->route('workCalendar');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('work_calendars', 'code')
                    ->ignore($workCalendar ? $workCalendar->id : null),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Messages personnalisés.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du calendrier est obligatoire.',
            'name.string' => 'Le nom du calendrier doit être une chaîne de caractères.',
            'name.max' => 'Le nom du calendrier ne peut pas dépasser 255 caractères.',

            'code.required' => 'Le code du calendrier est obligatoire.',
            'code.string' => 'Le code du calendrier doit être une chaîne de caractères.',
            'code.max' => 'Le code du calendrier ne peut pas dépasser 100 caractères.',
            'code.unique' => 'Ce code de calendrier existe déjà.',

            'description.string' => 'La description doit être une chaîne de caractères.',

            'is_active.boolean' => 'Le statut actif doit être un booléen.',
        ];
    }

    /**
     * Données préparées avant validation.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('is_active')) {
            $this->merge([
                'is_active' => true,
            ]);
        }
    }
}