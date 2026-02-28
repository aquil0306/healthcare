<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient'));
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date|before:today',
            'national_id' => ['sometimes', 'string', 'max:255', 'unique:patients,national_id,' . $patient->id],
            'insurance_number' => 'sometimes|string|max:255',
        ];
    }
}

