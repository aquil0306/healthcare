<?php

namespace Tests\Feature;

use App\Models\Hospital;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hospital_can_submit_referral_with_valid_api_key(): void
    {
        $hospital = Hospital::factory()->create([
            'status' => 'active',
            'api_key' => 'test-api-key-123',
        ]);

        $response = $this->postJson('/api/v1/hospital/referrals', [
            'patient' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '123456789',
                'insurance_number' => 'INS123',
            ],
            'urgency' => 'routine',
            'diagnosis_codes' => ['I10'],
            'clinical_notes' => 'Patient requires follow-up',
            'external_referral_id' => 'EXT123',
        ], [
            'X-API-Key' => 'test-api-key-123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'referral_id',
                    'status',
                ],
            ]);
    }

    public function test_hospital_cannot_submit_referral_without_api_key(): void
    {
        $response = $this->postJson('/api/v1/hospital/referrals', [
            'patient' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '123456789',
                'insurance_number' => 'INS123',
            ],
            'urgency' => 'routine',
            'diagnosis_codes' => ['I10'],
            'clinical_notes' => 'Patient requires follow-up',
        ]);

        $response->assertStatus(401);
    }

    public function test_duplicate_referral_prevention(): void
    {
        $hospital = Hospital::factory()->create([
            'status' => 'active',
            'api_key' => 'test-api-key-123',
        ]);

        // First submission
        $this->postJson('/api/v1/hospital/referrals', [
            'patient' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '123456789',
                'insurance_number' => 'INS123',
            ],
            'urgency' => 'routine',
            'diagnosis_codes' => ['I10'],
            'clinical_notes' => 'Patient requires follow-up',
            'external_referral_id' => 'EXT123',
        ], [
            'X-API-Key' => 'test-api-key-123',
        ]);

        // Duplicate submission
        $response = $this->postJson('/api/v1/hospital/referrals', [
            'patient' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '123456789',
                'insurance_number' => 'INS123',
            ],
            'urgency' => 'routine',
            'diagnosis_codes' => ['I10'],
            'clinical_notes' => 'Patient requires follow-up',
            'external_referral_id' => 'EXT123',
        ], [
            'X-API-Key' => 'test-api-key-123',
        ]);

        $response->assertStatus(409);
    }
}
