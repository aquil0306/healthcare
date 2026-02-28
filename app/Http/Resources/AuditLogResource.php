<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
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
            'referral_id' => $this->referral_id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'field' => $this->field,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'referral' => $this->whenLoaded('referral', fn () => [
                'id' => $this->referral->id,
                'patient_id' => $this->referral->patient_id,
                'hospital_id' => $this->referral->hospital_id,
                'status' => $this->referral->status,
                'urgency' => $this->referral->urgency,
            ]),
        ];
    }
}
