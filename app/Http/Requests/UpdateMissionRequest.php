<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMissionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [

            /**
             * =========================
             * BASE
             * =========================
             */
            'departure_date_base_planned' => 'sometimes|nullable|date',
            'departure_time_base_planned' => 'sometimes|nullable',

            'arrival_date_base_planned' => 'sometimes|nullable|date',
            'arrival_time_base_planned' => 'sometimes|nullable',

            'departure_date_base_actual' => 'sometimes|nullable|date',
            'departure_time_base_actual' => 'sometimes|nullable',

            'arrival_date_base_actual' => 'sometimes|nullable|date',
            'arrival_time_base_actual' => 'sometimes|nullable',


            /**
             * =========================
             * SITE
             * =========================
             */
            'departure_date_site_planned' => 'sometimes|nullable|date',
            'departure_time_site_planned' => 'sometimes|nullable',

            'arrival_date_site_planned' => 'sometimes|nullable|date',
            'arrival_time_site_planned' => 'sometimes|nullable',

            'departure_date_site_actual' => 'sometimes|nullable|date',
            'departure_time_site_actual' => 'sometimes|nullable',

            'arrival_date_site_actual' => 'sometimes|nullable|date',
            'arrival_time_site_actual' => 'sometimes|nullable',


            /**
             * =========================
             * GENERAL
             * =========================
             */
            'destination' => 'sometimes|nullable|string',
            'title' => 'sometimes|nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $fields = [
            'departure_date_base_planned',
            'arrival_date_base_planned',
            'departure_date_base_actual',
            'arrival_date_base_actual',

            'departure_date_site_planned',
            'arrival_date_site_planned',
            'departure_date_site_actual',
            'arrival_date_site_actual',
        ];

        $data = [];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                $data[$field] = $this->formatDate($this->$field);
            }
        }

        $this->merge($data);
    }

    private function formatDate($date)
    {
        if (!$date) return null;

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (Exception $e) {
            return $date;
        }
    }
}