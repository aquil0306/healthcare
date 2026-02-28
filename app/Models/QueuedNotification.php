<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueuedNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'referral_id',
        'message',
        'type',
        'channels',
        'queued_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'queued_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the channels attribute with default value if null
     */
    public function getChannelsAttribute($value): array
    {
        // The cast will handle JSON decoding, but if null, return default
        if ($value === null) {
            return ['in_app', 'email'];
        }

        // If already an array (from cast), return it
        if (is_array($value)) {
            return $value;
        }

        // If it's a JSON string, decode it
        $decoded = json_decode($value, true);

        return $decoded ?? ['in_app', 'email'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function markAsProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }
}
