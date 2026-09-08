<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicHolidayRequest extends FormRequest
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
        return [
            'work_calendar_id' => [
                'required',
                'integer',
                'exists:work_calendars,id',
            ],

            'date' => [
                'required',
                'date',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'counts_for_leave' => [
                'nullable',
                'boolean',
            ],

            'is_recurring' => [
                'nullable',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Messages personnalisés.
     */
    public function messages(): array
    {
        return [
            'work_calendar_id.required' =>
                'Le calendrier de travail est obligatoire.',

            'work_calendar_id.integer' =>
                'Le calendrier de travail sélectionné est invalide.',

            'work_calendar_id.exists' =>
                'Le calendrier de travail sélectionné n’existe pas.',

            'date.required' =>
                'La date du jour férié est obligatoire.',

            'date.date' =>
                'La date du jour férié est invalide.',

            'name.required' =>
                'Le nom du jour férié est obligatoire.',

            'name.string' =>
                'Le nom du jour férié doit être une chaîne de caractères.',

            'name.max' =>
                'Le nom du jour férié ne peut pas dépasser 255 caractères.',

            'counts_for_leave.boolean' =>
                'Le champ "compte pour les congés" doit être vrai ou faux.',

            'is_recurring.boolean' =>
                'Le champ "récurrent" doit être vrai ou faux.',

            'description.string' =>
                'La description doit être une chaîne de caractères.',
        ];
    }

    /**
     * Préparation des données avant validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'counts_for_leave' => $this->has('counts_for_leave')
                ? filter_var(
                    $this->input('counts_for_leave'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
                : false,

            'is_recurring' => $this->has('is_recurring')
                ? filter_var(
                    $this->input('is_recurring'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
                : false,
        ]);
    }
}
