<?php

namespace App\Services;

use App\Models\Referral;
use App\Repositories\ReferralRepository;
use Illuminate\Support\Facades\Log;

class EscalationService
{
    private ReferralRepository $referralRepository;

    private NotificationService $notificationService;

    private int $escalationMinutes = 2;

    public function __construct(
        ReferralRepository $referralRepository,
        NotificationService $notificationService
    ) {
        $this->referralRepository = $referralRepository;
        $this->notificationService = $notificationService;
    }

    public function checkAndEscalate(): void
    {
        $emergencyReferrals = $this->referralRepository
            ->getEmergencyUnacknowledged($this->escalationMinutes)
            ->get();

        foreach ($emergencyReferrals as $referral) {
            // Double-check to prevent race conditions
            if ($referral->acknowledged_at === null &&
                $referral->status !== 'completed' &&
                $referral->status !== 'cancelled' &&
                ! $referral->auditLogs()->where('action', 'escalated')->exists()) {
                $this->escalate($referral);
            }
        }
    }

    public function escalate(Referral $referral): void
    {
        Log::info("Escalating emergency referral #{$referral->id}");

        // Notify admins
        $this->notificationService->notifyAdminsForEscalation($referral);

        // Log escalation
        $referral->auditLogs()->create([
            'user_id' => null, // System action
            'action' => 'escalated',
            'field' => 'status',
            'old_value' => $referral->status,
            'new_value' => 'escalated',
            'metadata' => [
                'reason' => 'Not acknowledged within 2 minutes',
                'escalated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
