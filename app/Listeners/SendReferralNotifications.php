<?php

namespace App\Listeners;

use App\Events\ReferralTriaged;
use App\Services\NotificationService;

class SendReferralNotifications
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function handle(ReferralTriaged $event): void
    {
        $this->notificationService->notifyStaffForReferral($event->referral);
    }
}
