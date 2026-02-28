<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIcd10CodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('icd10_code'));
    }

    public function rules(): array
    {
        $icd10Code = $this->route('icd10_code');

        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('icd10_codes', 'code')->ignore($icd10Code->id)],
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:20',
            'category_description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }
}
