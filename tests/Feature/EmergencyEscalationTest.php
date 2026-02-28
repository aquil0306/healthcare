<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\ReferralRepository;
use App\Services\EscalationService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmergencyEscalationTest extends TestCase
{
    use RefreshDatabase;

    private EscalationService $escalationService;
    private ReferralRepository $referralRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->escalationService = app(EscalationService::class);
        $this->referralRepository = app(ReferralRepository::class);
        Event::fake();
        Queue::fake();
    }

    public function test_escalates_emergency_referral_not_acknowledged_within_2_minutes(): void
    {
        // Create admin staff
        $admin = Staff::factory()->create(['role' => 'admin']);

        // Create emergency referral older than 2 minutes
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'status' => 'triaged', // Not acknowledged
            'created_at' => now()->subMinutes(3),
        ]);

        $this->escalationService->checkAndEscalate();

        // Verify escalation audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'escalated',
            'field' => 'status',
        ]);

        // Verify notification was queued for admin
        Queue::assertPushed(\App\Jobs\SendEmailNotification::class);
    }

    public function test_does_not_escalate_acknowledged_emergency_referral(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'status' => 'acknowledged',
            'acknowledged_at' => now()->subMinute(),
            'created_at' => now()->subMinutes(3),
        ]);

        $this->escalationService->checkAndEscalate();

        // Should not create escalation log
        $this->assertDatabaseMissing('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'escalated',
        ]);
    }

    public function test_does_not_escalate_referral_less_than_2_minutes_old(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'status' => 'triaged',
            'created_at' => now()->subMinute(), // Only 1 minute old
        ]);

        $this->escalationService->checkAndEscalate();

        // Should not escalate
        $this->assertDatabaseMissing('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'escalated',
        ]);
    }

    public function test_does_not_escalate_non_emergency_referrals(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'routine',
            'status' => 'triaged',
            'created_at' => now()->subMinutes(3),
        ]);

        $this->escalationService->checkAndEscalate();

        // Should not escalate routine referrals
        $this->assertDatabaseMissing('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'escalated',
        ]);
    }

    public function test_escalation_creates_audit_log_with_metadata(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'status' => 'triaged',
            'created_at' => now()->subMinutes(3),
        ]);

        $this->escalationService->escalate($referral);

        $auditLog = $referral->auditLogs()->where('action', 'escalated')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('status', $auditLog->field);
        $this->assertArrayHasKey('reason', $auditLog->metadata);
        $this->assertArrayHasKey('escalated_at', $auditLog->metadata);
        $this->assertStringContainsString('2 minutes', $auditLog->metadata['reason']);
    }
}

