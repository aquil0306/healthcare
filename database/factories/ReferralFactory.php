<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\Patient;
use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'hospital_id' => Hospital::factory(),
            'urgency' => $this->faker->randomElement(['routine', 'urgent', 'emergency']),
            'status' => 'submitted',
            'clinical_notes' => $this->faker->paragraph(),
            'department' => null,
            'ai_confidence_score' => null,
            'processed_at' => null,
            'assigned_staff_id' => null,
            'cancellation_reason' => null,
            'acknowledged_at' => null,
            'external_referral_id' => $this->faker->unique()->uuid(),
        ];
    }
}
