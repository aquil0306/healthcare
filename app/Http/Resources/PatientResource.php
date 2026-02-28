<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'date_of_birth_human' => $this->date_of_birth?->diffForHumans(),
            'national_id' => $this->national_id,
            'insurance_number' => $this->insurance_number,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
            'referrals' => $this->whenLoaded('referrals', fn() => ReferralResource::collection($this->referrals)),
        ];
    }
}

