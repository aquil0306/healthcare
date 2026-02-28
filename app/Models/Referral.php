<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Referral extends Model
{
    use HasFactory;
    protected $fillable = [
        'patient_id',
        'hospital_id',
        'urgency',
        'status',
        'clinical_notes',
        'department', // Keep for backward compatibility
        'department_id',
        'ai_confidence_score',
        'processed_at',
        'assigned_staff_id',
        'cancellation_reason',
        'acknowledged_at',
        'external_referral_id',
    ];

    protected function casts(): array
    {
        return [
            'urgency' => 'string',
            'status' => 'string',
            'ai_confidence_score' => 'decimal:2',
            'processed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function icd10Codes(): HasMany
    {
        return $this->hasMany(ReferralIcd10Code::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function queuedNotifications(): HasMany
    {
        return $this->hasMany(QueuedNotification::class);
    }

    public function aiTriageLog(): HasOne
    {
        return $this->hasOne(AiTriageLog::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isEmergency(): bool
    {
        return $this->urgency === 'emergency';
    }

    public function canBeCancelled(): bool
    {
        return !$this->isCompleted();
    }
}
