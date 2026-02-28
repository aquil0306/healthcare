<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Staff::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:staff,email',
            'role' => ['required', Rule::in(['admin', 'doctor', 'coordinator'])], // Role is managed by Spatie, this is for validation only
            'department' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'password' => 'required|string|min:8',
            'is_available' => 'sometimes|boolean',
        ];
    }
}
