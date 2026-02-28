<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('staff'));
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'unique:users,email,'.$staff->user_id, 'unique:staff,email,'.$staff->id],
            'role' => ['sometimes', Rule::in(['admin', 'doctor', 'coordinator'])],
            'department' => 'nullable|string|max:255',
            'password' => 'sometimes|string|min:8',
            'is_available' => 'sometimes|boolean',
        ];
    }
}
