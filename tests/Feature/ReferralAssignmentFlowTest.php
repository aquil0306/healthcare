<?php

namespace Tests\Feature;

use App\Events\ReferralTriaged;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\QueuedNotification;
use App\Models\Referral;
use App\Models\ReferralIcd10Code;
use App\Models\Staff;
use App\Models\User;
use App\Services\AiTriageService;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Responses\AgentResponse;
use Tests\TestCase;

/**
 * Comprehensive test for the complete referral assignment flow:
 * 1. AI Triage assigns proper department
 * 2. Referral assigned to staff
 * 3. If staff unavailable, notification is queued
 * 4. If staff available, notification is sent immediately
 */
class ReferralAssignmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private AiTriageService $aiTriageService;
    private NotificationService $notificationService;
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Event::fake();
        $this->notificationService = app(NotificationService::class);
        $this->auditService = app(AuditService::class);
    }

    /**
     * Test complete flow: AI Triage -> Department Assignment -> Staff Assignment -> Notification Queuing
     * This is the most important test case covering the entire referral workflow
     */
    public function test_complete_referral_flow_with_unavailable_staff_queues_notification(): void
    {
        // Step 1: Create hospital and patient
        $hospital = Hospital::factory()->create([
            'name' => 'Test Hospital',
            'api_key' => 'test-api-key-123',
        ]);

        $patient = Patient::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'national_id' => '1234567890',
        ]);

        // Step 2: Create department (no factory needed, create directly)
        $department = Department::create([
            'name' => 'Cardiology',
            'code' => 'CARD',
        ]);

        // Step 3: Create referral with ICD-10 codes (for AI triage)
        $referral = Referral::factory()->create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
            'department_id' => null, // Will be set by AI
            'department' => null, // Will be set by AI
            'urgency' => 'routine', // Default, will be updated by AI
            'status' => 'submitted',
            'clinical_notes' => 'Patient presents with chest pain and shortness of breath. ECG shows ST elevation.',
        ]);

        // Add ICD-10 codes for AI triage
        ReferralIcd10Code::factory()->create([
            'referral_id' => $referral->id,
            'code' => 'I21.9', // Acute myocardial infarction
        ]);

        // Step 4: Create UNAVAILABLE staff member in cardiology department
        $staff = Staff::factory()->create([
            'role' => 'doctor',
            'department' => 'cardiology',
            'is_available' => false, // STAFF IS UNAVAILABLE
        ]);
        $user = User::factory()->create();
        $staff->update(['user_id' => $user->id]);

        // Step 5: Mock AI response for triage and trigger triage
        $this->mockAiTriageResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.92,
            'reasoning' => 'Chest pain with ST elevation indicates cardiac emergency requiring cardiology department',
        ]);

        // Step 6: Trigger AI Triage
        $aiTriageService = app(AiTriageService::class);
        $aiTriageService->triageReferral($referral);

        // Step 7: Verify AI triage results
        $referral->refresh();
        $this->assertEquals('cardiology', $referral->department, 'AI should assign cardiology department');
        $this->assertEquals('urgent', $referral->urgency, 'AI should set urgency to urgent');
        $this->assertEquals(0.92, $referral->ai_confidence_score, 'AI confidence score should be set');
        $this->assertEquals('triaged', $referral->status, 'Referral status should be triaged');
        $this->assertNotNull($referral->processed_at, 'Processed at should be set');

        // Step 8: Assign referral to staff
        $oldStatus = $referral->status;
        $oldStaffId = $referral->assigned_staff_id;
        $referral->update([
            'assigned_staff_id' => $staff->id,
            'status' => 'assigned',
        ]);

        // Log the staff assignment change
        $this->auditService->logChange(
            $referral,
            'assigned',
            'assigned_staff_id',
            $oldStaffId,
            $staff->id
        );

        // Log the status change if it changed
        if ($oldStatus !== 'assigned') {
            $this->auditService->logChange(
                $referral,
                'status_changed',
                'status',
                $oldStatus,
                'assigned'
            );
        }

        // Step 9: Notify staff of assignment (should queue since staff is unavailable)
        $this->notificationService->notifyStaffOfAssignment($staff, $referral);

        // Step 10: Verify notification was QUEUED (not sent immediately)
        $this->assertDatabaseHas('queued_notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'type' => 'assignment',
            'processed_at' => null, // Not processed yet
        ]);

        // Step 11: Verify NO immediate notification was created
        $this->assertDatabaseMissing('notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
        ]);

        // Step 12: Verify email job was NOT queued (since staff unavailable)
        Queue::assertNotPushed(\App\Jobs\SendEmailNotification::class);

        // Step 13: Verify audit log was created for assignment
        $this->assertDatabaseHas('audit_logs', [
            'referral_id' => $referral->id,
            'action' => 'assigned',
            'field' => 'assigned_staff_id',
        ]);
    }

    /**
     * Test complete flow with AVAILABLE staff - notification should be sent immediately
     */
    public function test_complete_referral_flow_with_available_staff_sends_notification(): void
    {
        // Step 1: Create hospital and patient
        $hospital = Hospital::factory()->create([
            'name' => 'Test Hospital',
            'api_key' => 'test-api-key-456',
        ]);

        $patient = Patient::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'national_id' => '0987654321',
        ]);

        // Step 2: Create referral with ICD-10 codes
        $referral = Referral::factory()->create([
            'hospital_id' => $hospital->id,
            'patient_id' => $patient->id,
            'department' => null,
            'urgency' => 'routine', // Default, will be updated by AI
            'status' => 'submitted',
            'clinical_notes' => 'Patient with severe headache and neurological symptoms.',
        ]);

        ReferralIcd10Code::factory()->create([
            'referral_id' => $referral->id,
            'code' => 'G43.9', // Migraine
        ]);

        // Step 3: Create AVAILABLE staff member in neurology department
        $staff = Staff::factory()->create([
            'role' => 'doctor',
            'department' => 'neurology',
            'is_available' => true, // STAFF IS AVAILABLE
        ]);
        $user = User::factory()->create();
        $staff->update(['user_id' => $user->id]);

        // Step 4: Mock AI response for triage and trigger triage
        $this->mockAiTriageResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'neurology',
            'confidence_score' => 0.88,
            'reasoning' => 'Neurological symptoms require neurology department assessment',
        ]);

        // Step 5: Trigger AI Triage
        $aiTriageService = app(AiTriageService::class);
        $aiTriageService->triageReferral($referral);

        // Step 6: Verify AI triage assigned neurology department
        $referral->refresh();
        $this->assertEquals('neurology', $referral->department, 'AI should assign neurology department');
        $this->assertEquals('urgent', $referral->urgency, 'AI should set urgency to urgent');

        // Step 7: Assign referral to available staff
        $referral->update([
            'assigned_staff_id' => $staff->id,
            'status' => 'assigned',
        ]);

        // Step 8: Notify staff of assignment (should send immediately since staff is available)
        $this->notificationService->notifyStaffOfAssignment($staff, $referral);

        // Step 9: Verify notification was SENT immediately (not queued)
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'type' => 'assignment',
            'channel' => 'in_app',
        ]);

        // Step 10: Verify NO queued notification was created
        $this->assertDatabaseMissing('queued_notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'processed_at' => null,
        ]);

        // Step 11: Verify email job was queued (since staff is available)
        Queue::assertPushed(\App\Jobs\SendEmailNotification::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    /**
     * Test that queued notifications are processed when staff becomes available
     */
    public function test_queued_notification_processed_when_staff_becomes_available(): void
    {
        // Step 1: Create referral and unavailable staff
        $referral = Referral::factory()->create([
            'department' => 'cardiology',
            'status' => 'assigned',
        ]);

        $staff = Staff::factory()->create([
            'role' => 'doctor',
            'department' => 'cardiology',
            'is_available' => false, // Initially unavailable
        ]);
        $user = User::factory()->create();
        $staff->update(['user_id' => $user->id]);

        // Step 2: Queue notification (staff unavailable)
        $this->notificationService->notifyStaffOfAssignment($staff, $referral);

        // Step 3: Verify notification was queued
        $this->assertDatabaseHas('queued_notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'processed_at' => null,
        ]);

        // Step 4: Staff becomes available
        $staff->update(['is_available' => true]);

        // Step 5: Process queued notifications
        $this->notificationService->processQueuedNotificationsForStaff($staff);

        // Step 6: Verify queued notification was processed
        $queuedNotification = QueuedNotification::where('staff_id', $staff->id)
            ->where('referral_id', $referral->id)
            ->first();
        
        $this->assertNotNull($queuedNotification->processed_at, 'Queued notification should be marked as processed');

        // Step 7: Verify actual notification was created
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'type' => 'assignment',
        ]);

        // Step 8: Verify email job was queued
        Queue::assertPushed(\App\Jobs\SendEmailNotification::class);
    }

    /**
     * Test emergency referral flow with SMS notification
     */
    public function test_emergency_referral_sends_sms_notification(): void
    {
        // Create emergency referral
        $referral = Referral::factory()->create([
            'urgency' => 'emergency',
            'department' => 'cardiology',
            'status' => 'assigned',
        ]);

        $staff = Staff::factory()->create([
            'role' => 'doctor',
            'department' => 'cardiology',
            'is_available' => true,
        ]);
        $user = User::factory()->create();
        $staff->update(['user_id' => $user->id]);

        // Notify staff
        $this->notificationService->notifyStaffOfAssignment($staff, $referral);

        // Verify SMS job was queued for emergency
        Queue::assertPushed(\App\Jobs\SendSmsNotification::class);

        // Verify in-app notification was created
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'channel' => 'in_app',
        ]);

        // Verify SMS notification record was created
        $this->assertDatabaseHas('notifications', [
            'staff_id' => $staff->id,
            'referral_id' => $referral->id,
            'channel' => 'sms',
        ]);
    }

    /**
     * Mock AI triage response to avoid real API calls
     * This mocks the Laravel AI SDK to return the expected response
     */
    private function mockAiTriageResponse(array $responseData): void
    {
        // Create a mock AgentResponse - use property access (not method)
        $mockResponse = $this->createMock(AgentResponse::class);
        $mockResponse->text = json_encode($responseData);
        $mockResponse->invocationId = 'test-invocation-id';

        // Create a mock TextProvider
        $mockProvider = $this->createMock(TextProvider::class);
        $mockProvider->method('prompt')
            ->willReturn($mockResponse);
        $mockProvider->method('defaultTextModel')
            ->willReturn('gpt-4o');

        // Create a mock AiManager
        $mockAiManager = $this->createMock(AiManager::class);
        $mockAiManager->method('textProvider')
            ->willReturn($mockProvider);

        // Bind the mock to the container - this will be used when AiTriageService is resolved
        $this->app->instance(AiManager::class, $mockAiManager);
    }
}

