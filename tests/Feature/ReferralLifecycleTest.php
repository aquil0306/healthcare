<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\Staff;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReferralLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
        Event::fake(); // Prevent real events from firing
    }

    public function test_referral_can_be_assigned_to_staff(): void
    {
        $referral = Referral::factory()->create([
            'status' => 'triaged',
            'assigned_staff_id' => null,
        ]);

        $staff = Staff::factory()->create();
        $oldStaffId = $referral->assigned_staff_id;

        $referral->update([
            'assigned_staff_id' => $staff->id,
            'status' => 'assigned',
        ]);

        $this->auditService->logChange(
            $referral,
            'assigned',
            'assigned_staff_id',
            $oldStaffId,
            $staff->id
        );

        $referral->refresh();
        $this->assertEquals('assigned', $referral->status);
        $this->assertEquals($staff->id, $referral->assigned_staff_id);
        
        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'assigned',
            'field' => 'assigned_staff_id',
        ]);
    }

    public function test_referral_can_be_cancelled_with_reason(): void
    {
        $referral = Referral::factory()->create([
            'status' => 'assigned',
        ]);

        $oldStatus = $referral->status;
        $reason = 'Patient requested cancellation';

        $referral->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        $this->auditService->logChange(
            $referral,
            'cancelled',
            'status',
            $oldStatus,
            'cancelled',
            ['reason' => $reason]
        );

        $referral->refresh();
        $this->assertEquals('cancelled', $referral->status);
        $this->assertEquals($reason, $referral->cancellation_reason);
        
        // Verify audit log
        $auditLog = $referral->auditLogs()->where('action', 'cancelled')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('status', $auditLog->field);
        $this->assertEquals('cancelled', $auditLog->new_value);
        $this->assertArrayHasKey('reason', $auditLog->metadata);
    }

    public function test_completed_referral_cannot_be_cancelled(): void
    {
        $referral = Referral::factory()->create([
            'status' => 'completed',
        ]);

        $this->assertFalse($referral->canBeCancelled());
    }

    public function test_referral_status_transitions_are_logged(): void
    {
        $referral = Referral::factory()->create([
            'status' => 'submitted',
        ]);

        // Transition: submitted -> triaged
        $this->auditService->logChange(
            $referral,
            'status_changed',
            'status',
            'submitted',
            'triaged'
        );
        $referral->update(['status' => 'triaged']);

        // Transition: triaged -> assigned
        $this->auditService->logChange(
            $referral,
            'status_changed',
            'status',
            'triaged',
            'assigned'
        );
        $referral->update(['status' => 'assigned']);

        // Verify all transitions logged
        $auditLogs = $referral->auditLogs()->where('field', 'status')->get();
        $this->assertCount(2, $auditLogs);
        
        $this->assertEquals('submitted', $auditLogs[0]->old_value);
        $this->assertEquals('triaged', $auditLogs[0]->new_value);
        
        $this->assertEquals('triaged', $auditLogs[1]->old_value);
        $this->assertEquals('assigned', $auditLogs[1]->new_value);
    }

    public function test_staff_can_acknowledge_referral(): void
    {
        $staff = Staff::factory()->create();
        $referral = Referral::factory()->create([
            'status' => 'assigned',
            'assigned_staff_id' => $staff->id,
            'acknowledged_at' => null,
        ]);

        $referral->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        $this->auditService->logChange(
            $referral,
            'acknowledged',
            'status',
            'assigned',
            'acknowledged'
        );

        $referral->refresh();
        $this->assertEquals('acknowledged', $referral->status);
        $this->assertNotNull($referral->acknowledged_at);
    }
}

