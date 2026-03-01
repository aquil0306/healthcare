<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'department', // Keep for backward compatibility
        'department_id',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedReferrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'assigned_staff_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function queuedNotifications(): HasMany
    {
        return $this->hasMany(QueuedNotification::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the role from Spatie (first role assigned to user)
     * This accessor provides backward compatibility for code that uses $staff->role
     */
    public function getRoleAttribute(): ?string
    {
        if (!$this->relationLoaded('user')) {
            $this->load('user.roles');
        }
        
        if (!$this->user) {
            return null;
        }
        
        // Use the loaded relationship if available, otherwise query
        if ($this->user->relationLoaded('roles')) {
            $role = $this->user->roles->first();
        } else {
            $role = $this->user->roles()->first();
        }
        
        return $role ? $role->name : null;
    }

    public function isAdmin(): bool
    {
        return $this->user && $this->user->hasRole('admin');
    }

    public function isDoctor(): bool
    {
        return $this->user && $this->user->hasRole('doctor');
    }

    public function isCoordinator(): bool
    {
        return $this->user && $this->user->hasRole('coordinator');
    }

    public function canReceiveReferralForDepartment(string $department): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->department === $department;
    }
}
