<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('hospital'));
    }

    public function rules(): array
    {
        $hospital = $this->route('hospital');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => ['sometimes', 'string', 'max:255', 'unique:hospitals,code,'.$hospital->id],
            'status' => 'sometimes|in:active,suspended',
        ];
    }
}
