<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icd10Code extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'category',
        'category_description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function referralIcd10Codes(): HasMany
    {
        return $this->hasMany(ReferralIcd10Code::class);
    }

    /**
     * Departments that can handle this ICD-10 code
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'icd10_code_department')
            ->withPivot(['priority', 'is_primary', 'notes'])
            ->withTimestamps()
            ->orderByPivot('priority')
            ->orderByPivot('is_primary', 'desc');
    }

    /**
     * Get the primary department for this ICD-10 code
     */
    public function primaryDepartment(): ?Department
    {
        return $this->departments()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get suggested departments for this ICD-10 code (ordered by priority)
     */
    public function suggestedDepartments(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->departments()
            ->orderByPivot('priority')
            ->orderByPivot('is_primary', 'desc')
            ->get();
    }
}
