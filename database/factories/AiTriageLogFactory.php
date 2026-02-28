<?php

namespace Database\Factories;

use App\Models\AiTriageLog;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiTriageLogFactory extends Factory
{
    protected $model = AiTriageLog::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['success', 'failed', 'retrying']);
        
        return [
            'referral_id' => Referral::factory(),
            'input_data' => [
                'diagnosis_codes' => ['I10', 'I20'],
                'clinical_notes' => 'Patient presents with symptoms...',
            ],
            'output_data' => $status === 'success' ? [
                'urgency' => 'urgent',
                'suggested_department' => 'cardiology',
                'confidence_score' => $this->faker->randomFloat(2, 0.5, 1.0),
            ] : null,
            'status' => $status,
            'retry_count' => $status === 'retrying' ? $this->faker->numberBetween(1, 3) : 0,
            'error_message' => $status === 'failed' ? 'AI service unavailable' : null,
        ];
    }
}
