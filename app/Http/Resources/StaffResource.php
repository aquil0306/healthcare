<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'department' => $this->department,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
        ];
    }
}

