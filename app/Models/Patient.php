<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'national_id',
        'insurance_number',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    // Encryption accessors and mutators for PII
    public function getFirstNameAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getLastNameAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getNationalIdAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setNationalIdAttribute($value)
    {
        $this->attributes['national_id'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getInsuranceNumberAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setInsuranceNumberAttribute($value)
    {
        $this->attributes['insurance_number'] = $value ? Crypt::encryptString($value) : null;
    }
}
