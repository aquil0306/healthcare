<?php

namespace App\Observers;

use App\Models\Staff;
use App\Services\NotificationService;

class StaffObserver
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Handle the Staff "updated" event.
     * Process queued notifications when staff becomes available
     */
    public function updated(Staff $staff): void
    {
        // Check if availability changed from unavailable to available
        if ($staff->is_available && $staff->wasChanged('is_available')) {
            // Process any queued notifications for this staff member
            $this->notificationService->processQueuedNotificationsForStaff($staff);
        }
    }
}
