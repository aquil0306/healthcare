<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle case where resource might not be a model (e.g., string or null)
        if (!$this->resource || !is_object($this->resource)) {
            return [];
        }
        
        // Make API key visible for admin resources
        if (method_exists($this->resource, 'makeVisible')) {
            $this->resource->makeVisible('api_key');
        }
        
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'code' => $this->code ?? null,
            'status' => $this->status ?? null,
            'api_key' => $this->api_key ?? null,
            'created_at' => $this->created_at?->diffForHumans(),
            'created_at_raw' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->diffForHumans(),
            'updated_at_raw' => $this->updated_at?->toIso8601String(),
        ];
    }
}

