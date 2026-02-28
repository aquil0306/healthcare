<?php

namespace Database\Factories;

use App\Models\Referral;
use App\Models\ReferralIcd10Code;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReferralIcd10CodeFactory extends Factory
{
    protected $model = ReferralIcd10Code::class;

    public function definition(): array
    {
        // Common ICD-10 codes
        $icd10Codes = [
            'I10', // Essential hypertension
            'I20', // Angina pectoris
            'I50', // Heart failure
            'G93', // Other disorders of brain
            'M25', // Other joint disorders
            'K59', // Other functional intestinal disorders
            'J44', // Other chronic obstructive pulmonary disease
            'E11', // Type 2 diabetes mellitus
            'F32', // Major depressive disorder
            'N18', // Chronic kidney disease
        ];

        return [
            'referral_id' => Referral::factory(),
            'code' => $this->faker->randomElement($icd10Codes),
        ];
    }
}
