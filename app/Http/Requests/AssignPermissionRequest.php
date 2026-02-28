<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignPermission', \App\Models\Staff::class);
    }

    public function rules(): array
    {
        return [
            'permission_id' => 'required|exists:permissions,id',
        ];
    }
}
