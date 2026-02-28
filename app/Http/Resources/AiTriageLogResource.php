<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiTriageLogResource extends JsonResource
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
            'input_data' => $this->input_data,
            'output_data' => $this->output_data,
            'status' => $this->status,
            'retry_count' => $this->retry_count,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
        ];
    }
}

