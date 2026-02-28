<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class QueueNotificationForUnavailableStaff implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Referral $referral
    ) {}

    public function handle(NotificationService $service): void
    {
        // Check again if staff is available
        $service->notifyStaffForReferral($this->referral);
    }
}
