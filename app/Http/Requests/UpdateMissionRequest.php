<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionRequest extends FormRequest
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

            'start_date_planned' => 'sometimes|nullable|date',
            'end_date_planned' => 'sometimes|nullable|date',

            'start_date_actual' => 'sometimes|nullable|date',
            'end_date_actual' => 'sometimes|nullable|date',

            'departure_time_planned' => 'sometimes|nullable',
            'return_time_planned' => 'sometimes|nullable',

            'departure_time_actual' => 'sometimes|nullable',
            'return_time_actual' => 'sometimes|nullable',

            'destination' => 'sometimes|nullable|string',
            'title' => 'sometimes|nullable|string',

        ];
    }

 protected function prepareForValidation()
{
    $data = [];

    if ($this->has('start_date_planned')) {
        $data['start_date_planned'] = $this->formatDate(
            $this->start_date_planned
        );
    }

    if ($this->has('end_date_planned')) {
        $data['end_date_planned'] = $this->formatDate(
            $this->end_date_planned
        );
    }

    if ($this->has('start_date_actual')) {
        $data['start_date_actual'] = $this->formatDate(
            $this->start_date_actual
        );
    }

    if ($this->has('end_date_actual')) {
        $data['end_date_actual'] = $this->formatDate(
            $this->end_date_actual
        );
    }

    $this->merge($data);
}

private function formatDate($date)
{
    if (!$date) return null;

    try {
        // format FR: 02-05-2026
        return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
    } catch (Exception $e) {
        return $date; // fallback si déjà au bon format
    }
}
}
