<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function logChange(Referral $referral, string $action, ?string $field = null, $oldValue = null, $newValue = null, array $metadata = []): void
    {
        AuditLog::create([
            'referral_id' => $referral->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue ? (is_array($oldValue) ? json_encode($oldValue) : $oldValue) : null,
            'new_value' => $newValue ? (is_array($newValue) ? json_encode($newValue) : $newValue) : null,
            'metadata' => $metadata,
        ]);
    }
}

