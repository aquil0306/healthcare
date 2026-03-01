<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Staff;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
        Queue::fake();
        Event::fake();
    }

    public function test_notifies_available_staff_matching_department_and_role(): void
    {
        // Create referral for cardiology department
        $referral = Referral::factory()->create([
            'department' => 'cardiology',
            'status' => 'triaged',
        ]);

        // Create available cardiology doctor
        $doctor = Staff::factory()->doctor()->create([
            'department' => 'cardiology',
            'is_available' => true,
        ]);

        // Create unavailable doctor (should not be notified)
        Staff::factory()->doctor()->create([
            'department' => 'cardiology',
            'is_available' => false,
        ]);

        // Create coordinator in different department (should not be notified)
        Staff::factory()->coordinator()->create([
            'department' => 'general',
            'is_available' => true,
        ]);

        $this->notificationService->notifyStaffForReferral($referral);

        // Verify notification was created for available doctor
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $doctor->id,
            'referral_id' => $referral->id,
            'channel' => 'in_app',
        ]);

        // Verify email job was queued
        Queue::assertPushed(\App\Jobs\SendEmailNotification::class, function ($job) use ($doctor) {
            return $job->user->id === $doctor->user_id;
        });
    }

    public function test_queues_notification_when_no_available_staff(): void
    {
        $referral = Referral::factory()->create([
            'department' => 'cardiology',
        ]);

        // Create unavailable doctor
        Staff::factory()->doctor()->create([
            'department' => 'cardiology',
            'is_available' => false,
        ]);

        $this->notificationService->notifyStaffForReferral($referral);

        // Should queue notification for later
        Queue::assertPushed(\App\Jobs\QueueNotificationForUnavailableStaff::class);
    }

    public function test_notifies_admins_for_escalation(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
        ]);

        $admin1 = Staff::factory()->admin()->create();
        $admin2 = Staff::factory()->admin()->create();

        $this->notificationService->notifyAdminsForEscalation($referral);

        // Verify both admins were notified
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $admin1->id,
            'referral_id' => $referral->id,
            'type' => 'escalation',
        ]);

        $this->assertDatabaseHas('notifications', [
            'staff_id' => $admin2->id,
            'referral_id' => $referral->id,
            'type' => 'escalation',
        ]);

        // Verify email jobs queued for both
        Queue::assertPushed(\App\Jobs\SendEmailNotification::class, 2);
    }

    public function test_sends_sms_for_emergency_referrals(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'department' => 'cardiology',
        ]);

        $doctor = Staff::factory()->doctor()->create([
            'department' => 'cardiology',
            'is_available' => true,
        ]);

        $this->notificationService->notifyStaffForReferral($referral);

        // Verify SMS job was queued for emergency
        Queue::assertPushed(\App\Jobs\SendSmsNotification::class);
    }

    public function test_does_not_send_sms_for_non_emergency_referrals(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'routine',
            'department' => 'cardiology',
        ]);

        $doctor = Staff::factory()->doctor()->create([
            'department' => 'cardiology',
            'is_available' => true,
        ]);

        $this->notificationService->notifyStaffForReferral($referral);

        // Should not queue SMS for routine referrals
        Queue::assertNotPushed(\App\Jobs\SendSmsNotification::class);
    }
}
