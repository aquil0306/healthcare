<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIcd10CodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Icd10Code::class);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:10|unique:icd10_codes,code',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|max:20',
            'category_description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }
}
