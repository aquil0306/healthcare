<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Services\AiTriageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Responses\AgentResponse;
use Tests\TestCase;

class AiTriageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_ai_triage_successfully_processes_referral(): void
    {
        // Mock AI response
        $aiResponse = [
            'urgency' => 'urgent',
            'suggested_department' => 'cardiology',
            'confidence_score' => 0.85,
        ];

        $mockResponse = $this->createMock(AgentResponse::class);
        $mockResponse->text = json_encode($aiResponse);
        $mockResponse->invocationId = 'test-invocation-id';

        $mockProvider = $this->createMock(TextProvider::class);
        $mockProvider->method('prompt')
            ->willReturn($mockResponse);
        $mockProvider->method('defaultTextModel')
            ->willReturn('gpt-4o');

        $mockAiManager = $this->createMock(AiManager::class);
        $mockAiManager->method('textProvider')
            ->willReturn($mockProvider);

        $this->app->instance(AiManager::class, $mockAiManager);

        $referral = Referral::factory()->create([
            'status' => 'submitted',
            'urgency' => 'routine',
        ]);
        $referral->icd10Codes()->create(['code' => 'I10']);

        $service = app(AiTriageService::class);
        $service->triageReferral($referral);

        $referral->refresh();
        $this->assertEquals('triaged', $referral->status);
        $this->assertEquals('urgent', $referral->urgency);
        $this->assertEquals('cardiology', $referral->department);
        $this->assertEquals(0.85, $referral->ai_confidence_score);
    }

    public function test_ai_triage_retries_on_failure(): void
    {
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

        $referral = Referral::factory()->create([
            'status' => 'submitted',
        ]);
        $referral->icd10Codes()->create(['code' => 'I10']);

        $service = app(AiTriageService::class);
        $service->triageReferral($referral);

        // Verify retry job was queued
        Queue::assertPushed(\App\Jobs\RetryAiTriage::class);
    }
}
