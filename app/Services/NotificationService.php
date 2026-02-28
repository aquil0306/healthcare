<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\Staff;
use App\Models\Notification;
use App\Models\QueuedNotification;
use App\Repositories\StaffRepository;
use Illuminate\Support\Facades\Queue;

class NotificationService
{
    private StaffRepository $staffRepository;

    public function __construct(StaffRepository $staffRepository)
    {
        $this->staffRepository = $staffRepository;
    }

    public function notifyStaffForReferral(Referral $referral): void
    {
        $department = $referral->department;
        $role = $this->getRoleForDepartment($department);

        // Get available staff for this department and role
        $staffMembers = $this->staffRepository->getAvailableByDepartmentAndRole($department, $role);

        if ($staffMembers->isEmpty()) {
            // Queue notification for when staff becomes available
            dispatch(new \App\Jobs\QueueNotificationForUnavailableStaff($referral));
            return;
        }

        foreach ($staffMembers as $staff) {
            $this->sendNotification($staff, $referral, 'New referral assigned to your department');
        }
    }

    public function notifyAdminsForEscalation(Referral $referral): void
    {
        $admins = $this->staffRepository->getAdmins();

        foreach ($admins as $admin) {
            $this->sendNotification(
                $admin,
                $referral,
                "Emergency referral #{$referral->id} requires immediate attention - not acknowledged within 2 minutes",
                'escalation'
            );
        }
    }

    /**
     * Notify a specific staff member that a referral has been assigned to them
     * If staff is not available, the notification will be queued in the database
     */
    public function notifyStaffOfAssignment(Staff $staff, Referral $referral): void
    {
        $message = "Referral #{$referral->id} has been assigned to you";
        if ($referral->patient) {
            $message .= " - Patient: {$referral->patient->first_name} {$referral->patient->last_name}";
        }
        if ($referral->urgency === 'emergency') {
            $message .= " [EMERGENCY]";
        }
        
        // Check if staff is available
        if (!$staff->is_available) {
            // Queue the notification in the database
            $this->queueNotification($staff, $referral, $message, 'assignment');
            return;
        }
        
        // Staff is available, send notification immediately
        $this->sendNotification($staff, $referral, $message, 'assignment');
    }

    /**
     * Queue a notification in the database for later delivery when staff becomes available
     */
    private function queueNotification(Staff $staff, Referral $referral, string $message, string $type, array $channels = ['in_app', 'email']): void
    {
        QueuedNotification::create([
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'message' => $message,
            'type' => $type,
            'channels' => $channels,
            'queued_at' => now(),
        ]);
    }

    /**
     * Process all queued notifications for a staff member when they become available
     */
    public function processQueuedNotificationsForStaff(Staff $staff): void
    {
        if (!$staff->is_available) {
            return; // Staff is not available, don't process
        }

        $queuedNotifications = QueuedNotification::where('staff_id', $staff->id)
            ->whereNull('processed_at')
            ->with('referral')
            ->get();

        foreach ($queuedNotifications as $queuedNotification) {
            // Double-check staff is still available
            $staff->refresh();
            if (!$staff->is_available) {
                break; // Staff became unavailable, stop processing
            }

            // Send the notification
            $this->sendNotification(
                $staff,
                $queuedNotification->referral,
                $queuedNotification->message,
                $queuedNotification->type,
                $queuedNotification->channels ?? ['in_app', 'email']
            );

            // Mark as processed
            $queuedNotification->markAsProcessed();
        }
    }

    public function sendNotification(Staff $staff, Referral $referral, string $message, string $type = 'referral', array $channels = ['in_app', 'email']): void
    {
        // In-app notification (always sent)
        $notification = Notification::create([
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'message' => $message,
            'channel' => 'in_app',
            'type' => $type,
            'sent_at' => now(),
        ]);

        // Email notification
        if (in_array('email', $channels) && $staff->user) {
            dispatch(new \App\Jobs\SendEmailNotification($staff->user, $referral, $message));
            
            // Create email notification record
            Notification::create([
                'staff_id' => $staff->id,
                'referral_id' => $referral->id,
                'message' => $message,
                'channel' => 'email',
                'type' => $type,
                'sent_at' => now(),
            ]);
        }

        // SMS for emergency or if explicitly requested
        if (in_array('sms', $channels) || ($referral->isEmergency() && !in_array('sms', $channels))) {
            dispatch(new \App\Jobs\SendSmsNotification($staff, $referral, $message));
            
            // Create SMS notification record
            Notification::create([
                'staff_id' => $staff->id,
                'referral_id' => $referral->id,
                'message' => $message,
                'channel' => 'sms',
                'type' => $type,
                'sent_at' => now(),
            ]);
        }

        // Slack notification (if enabled)
        if (in_array('slack', $channels) && config('notifications.slack.enabled', false)) {
            dispatch(new \App\Jobs\SendSlackNotification($staff, $referral, $message));
        }
    }

    private function getRoleForDepartment(string $department): string
    {
        // Map departments to roles - can be configured
        $mapping = [
            'cardiology' => 'doctor',
            'neurology' => 'doctor',
            'orthopedics' => 'doctor',
            'general' => 'coordinator',
        ];

        return $mapping[$department] ?? 'coordinator';
    }
}

