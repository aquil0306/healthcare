<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /**
     * ICD-10 codes that can be handled by this department
     */
    public function icd10Codes(): BelongsToMany
    {
        return $this->belongsToMany(Icd10Code::class, 'icd10_code_department')
            ->withPivot(['priority', 'is_primary', 'notes'])
            ->withTimestamps()
            ->orderByPivot('priority')
            ->orderByPivot('is_primary', 'desc');
    }

    /**
     * Get ICD-10 codes where this department is primary
     */
    public function primaryIcd10Codes(): BelongsToMany
    {
        return $this->icd10Codes()
            ->wherePivot('is_primary', true);
    }
}
