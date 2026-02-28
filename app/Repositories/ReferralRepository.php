<?php

namespace App\Repositories;

use App\Models\Referral;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReferralRepository extends BaseRepository
{
    public function __construct(Referral $model)
    {
        parent::__construct($model);
    }

    public function findByExternalId(int $hospitalId, string $externalId): ?Referral
    {
        return $this->model
            ->where('hospital_id', $hospitalId)
            ->where('external_referral_id', $externalId)
            ->first();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['patient', 'hospital', 'department', 'assignedStaff', 'icd10Codes.icd10Code']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getAssignedToStaff(int $staffId): Builder
    {
        return $this->model
            ->where('assigned_staff_id', $staffId)
            ->with(['patient', 'hospital', 'icd10Codes']);
    }

    public function getEmergencyUnacknowledged(int $minutes = 2): Builder
    {
        return $this->model
            ->where('urgency', 'emergency')
            ->whereNull('acknowledged_at') // Not acknowledged by any staff
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($minutes) {
                // Check from processed_at (when triaged) if available, otherwise from created_at
                $query->where(function ($q) use ($minutes) {
                    $q->whereNotNull('processed_at')
                      ->where('processed_at', '<=', now()->subMinutes($minutes));
                })->orWhere(function ($q) use ($minutes) {
                    $q->whereNull('processed_at')
                      ->where('created_at', '<=', now()->subMinutes($minutes));
                });
            })
            // Exclude already escalated referrals (check audit logs)
            ->whereDoesntHave('auditLogs', function ($query) {
                $query->where('action', 'escalated');
            });
    }
}

