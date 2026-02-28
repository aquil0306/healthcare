<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'staff_id' => $this->staff_id,
            'referral_id' => $this->referral_id,
            'message' => $this->message,
            'channel' => $this->channel,
            'type' => $this->type,
            'sent_at' => $this->sent_at?->diffForHumans(),
            'sent_at_raw' => $this->sent_at?->toIso8601String(),
            'read_at' => $this->read_at?->diffForHumans(),
            'read_at_raw' => $this->read_at?->toIso8601String(),
            'is_read' => $this->isRead(),
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
            'staff' => $this->whenLoaded('staff', fn () => new StaffResource($this->staff)),
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
