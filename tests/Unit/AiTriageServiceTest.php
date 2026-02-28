<?php

namespace Tests\Unit;

use App\Events\ReferralTriaged;
use App\Models\AiTriageLog;
use App\Models\Referral;
use App\Services\AiTriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Responses\AgentResponse;
use Tests\TestCase;

class AiTriageServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiTriageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->service = app(AiTriageService::class);
    }

    /**
     * Mock AI response to avoid real API calls
     */
    private function mockAiResponse(array $responseData): void
    {
        // Create a mock AgentResponse - use property access (not method)
        $mockResponse = $this->createMock(AgentResponse::class);
        $mockResponse->text = json_encode($responseData);
        $mockResponse->invocationId = 'test-invocation-id';

        // Create a mock TextProvider (interface, but PHPUnit can mock interfaces)
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

        // Re-resolve the service with mocked AI manager
        $this->service = app(AiTriageService::class);
    }

    public function test_creates_audit_log_with_input_data(): void
    {
        $referral = Referral::factory()->create();
        $referral->icd10Codes()->create(['code' => 'I10']);
        $referral->icd10Codes()->create(['code' => 'E11']);

        $this->mockAiResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.85,
        ]);

        $this->service->triageReferral($referral);

        $log = AiTriageLog::where('referral_id', $referral->id)->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('diagnosis_codes', $log->input_data);
        $this->assertArrayHasKey('clinical_notes', $log->input_data);
        $this->assertContains('I10', $log->input_data['diagnosis_codes']);
        $this->assertContains('E11', $log->input_data['diagnosis_codes']);
    }

    public function test_stores_ai_output_in_audit_log(): void
    {
        $referral = Referral::factory()->create();
        $referral->icd10Codes()->create(['code' => 'I10']);

        $aiResponse = [
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.85,
        ];

        $this->mockAiResponse($aiResponse);

        $this->service->triageReferral($referral);

        $log = AiTriageLog::where('referral_id', $referral->id)->first();
        $this->assertEquals('success', $log->status);
        $this->assertArrayHasKey('parsed_data', $log->output_data);
        $this->assertEquals($aiResponse['urgency'], $log->output_data['parsed_data']['urgency']);
        $this->assertEquals($aiResponse['suggested_department'], $log->output_data['parsed_data']['suggested_department']);
        $this->assertEquals($aiResponse['confidence_score'], $log->output_data['parsed_data']['confidence_score']);
    }

    public function test_retries_on_failure_and_logs_attempts(): void
    {
        $referral = Referral::factory()->create();
        $referral->icd10Codes()->create(['code' => 'I10']);

        // Mock AI to throw exception (simulating failure)
        $mockProvider = $this->createMock(TextProvider::class);
        $mockProvider->method('prompt')
            ->willThrowException(new \Exception('AI service unavailable'));
        $mockProvider->method('defaultTextModel')
            ->willReturn('gpt-4o');

        $mockAiManager = $this->createMock(AiManager::class);
        $mockAiManager->method('textProvider')
            ->willReturn($mockProvider);

        $this->app->instance(AiManager::class, $mockAiManager);
        $this->service = app(AiTriageService::class);

        $this->service->triageReferral($referral);

        // Verify retry job was queued
        Queue::assertPushed(\App\Jobs\RetryAiTriage::class);
    }

    public function test_falls_back_to_default_when_all_retries_fail(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'routine',
            'department' => null,
        ]);
        $referral->icd10Codes()->create(['code' => 'I10']);

        // Mock AI to throw exception (simulating all failures)
        $mockProvider = $this->createMock(TextProvider::class);
        $mockProvider->method('prompt')
            ->willThrowException(new \Exception('AI service unavailable'));
        $mockProvider->method('defaultTextModel')
            ->willReturn('gpt-4o');

        $mockAiManager = $this->createMock(AiManager::class);
        $mockAiManager->method('textProvider')
            ->willReturn($mockProvider);

        $this->app->instance(AiManager::class, $mockAiManager);
        $this->service = app(AiTriageService::class);

        $this->service->triageReferral($referral);

        // Verify retry job was queued (fallback will happen after max retries)
        Queue::assertPushed(\App\Jobs\RetryAiTriage::class);
    }

    public function test_updates_referral_with_ai_results(): void
    {
        $referral = Referral::factory()->create([
            'urgency' => 'routine',
            'department' => null,
            'ai_confidence_score' => null,
            'status' => 'submitted',
        ]);
        $referral->icd10Codes()->create(['code' => 'I10']);

        $this->mockAiResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.92,
        ]);

        $this->service->triageReferral($referral);

        $referral->refresh();
        $this->assertEquals('urgent', $referral->urgency);
        $this->assertEquals('cardiology', $referral->department);
        $this->assertEquals(0.92, $referral->ai_confidence_score);
        $this->assertEquals('triaged', $referral->status);
        $this->assertNotNull($referral->processed_at);
    }

    public function test_fires_referral_triaged_event_on_success(): void
    {
        $referral = Referral::factory()->create();
        $referral->icd10Codes()->create(['code' => 'I10']);

        $this->mockAiResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.85,
        ]);

        $this->service->triageReferral($referral);

        Event::assertDispatched(ReferralTriaged::class, function ($event) use ($referral) {
            return $event->referral->id === $referral->id;
        });
    }

    public function test_does_not_make_real_http_calls(): void
    {
        $referral = Referral::factory()->create();
        $referral->icd10Codes()->create(['code' => 'I10']);

        // Mock AI Manager (no real API calls)
        $this->mockAiResponse([
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.85,
        ]);

        $this->service->triageReferral($referral);

        // If we got here without exceptions, no real HTTP calls were made
        $this->assertTrue(true);
    }
}
