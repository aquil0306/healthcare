<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Icd10CodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'category' => $this->category,
            'category_description' => $this->category_description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
        ];
    }
}
