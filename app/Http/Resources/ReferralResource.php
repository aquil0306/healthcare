<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'hospital_id' => $this->hospital_id,
            'urgency' => $this->urgency,
            'status' => $this->status,
            'clinical_notes' => $this->clinical_notes,
            'department' => $this->department, // Keep old string field for backward compatibility
            'ai_confidence_score' => $this->ai_confidence_score,
            'processed_at' => $this->processed_at?->diffForHumans(),
            'processed_at_raw' => $this->processed_at?->toIso8601String(),
            'assigned_staff_id' => $this->assigned_staff_id,
            'cancellation_reason' => $this->cancellation_reason,
            'acknowledged_at' => $this->acknowledged_at?->diffForHumans(),
            'acknowledged_at_raw' => $this->acknowledged_at?->toIso8601String(),
            'external_referral_id' => $this->external_referral_id,
            'created_at' => $this->created_at->diffForHumans(),
            'created_at_raw' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->diffForHumans(),
            'updated_at_raw' => $this->updated_at->toIso8601String(),
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? new PatientResource($this->patient) : null),
            'hospital' => $this->whenLoaded('hospital', fn () => $this->hospital && is_object($this->hospital) ? new HospitalResource($this->hospital) : null),
            'department_resource' => $this->whenLoaded('department', fn () => $this->department && is_object($this->department) ? new DepartmentResource($this->department) : null),
            'assigned_staff' => $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff ? new StaffResource($this->assignedStaff) : null),
            'icd10_codes' => $this->whenLoaded('icd10Codes', fn () => ReferralIcd10CodeResource::collection($this->icd10Codes)),
            'audit_logs' => $this->whenLoaded('auditLogs', fn () => AuditLogResource::collection($this->auditLogs)),
            'ai_triage_log' => $this->whenLoaded('aiTriageLog', fn () => new AiTriageLogResource($this->aiTriageLog)),
        ];
    }
}
