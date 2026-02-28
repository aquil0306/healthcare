<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'patient' => 'required|array',
            'patient.first_name' => 'required|string|max:255',
            'patient.last_name' => 'required|string|max:255',
            'patient.date_of_birth' => 'required|date',
            'patient.national_id' => 'required|string|max:255',
            'patient.insurance_number' => 'required|string|max:255',
            'urgency' => 'required|in:routine,urgent,emergency',
            'diagnosis_codes' => 'required|array|min:1',
            'diagnosis_codes.*' => 'required|string|max:20',
            'clinical_notes' => 'required|string',
            'external_referral_id' => 'nullable|string|max:255',
        ];
    }
}
