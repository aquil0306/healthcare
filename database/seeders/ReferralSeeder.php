<?php

namespace Database\Seeders;

use App\Models\Icd10Code;
use App\Models\Referral;
use App\Models\ReferralIcd10Code;
use Illuminate\Database\Seeder;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        $referrals = Referral::factory(30)->create();

        // Get ICD-10 codes from the database (seeded by Icd10CodeSeeder)
        $icd10Codes = Icd10Code::whereIn('code', ['I10', 'I20', 'I50', 'G93', 'M25', 'K59', 'J44', 'E11', 'F32', 'N18'])
            ->where('is_active', true)
            ->get();

        // Fallback to codes if no ICD-10 codes exist in database yet
        if ($icd10Codes->isEmpty()) {
            $codeStrings = ['I10', 'I20', 'I50', 'G93', 'M25', 'K59', 'J44', 'E11', 'F32', 'N18'];

            foreach ($referrals as $referral) {
                $codeCount = rand(1, 3);
                $selectedCodes = array_rand(array_flip($codeStrings), $codeCount);

                if (! is_array($selectedCodes)) {
                    $selectedCodes = [$selectedCodes];
                }

                foreach ($selectedCodes as $code) {
                    ReferralIcd10Code::create([
                        'referral_id' => $referral->id,
                        'code' => $code,
                    ]);
                }
            }
        } else {
            foreach ($referrals as $referral) {
                // Add 1-3 ICD-10 codes per referral
                $codeCount = rand(1, min(3, $icd10Codes->count()));
                $selectedCodes = $icd10Codes->random($codeCount);

                // Ensure it's always a collection
                if (! ($selectedCodes instanceof \Illuminate\Support\Collection)) {
                    $selectedCodes = collect([$selectedCodes]);
                }

                foreach ($selectedCodes as $icd10Code) {
                    ReferralIcd10Code::create([
                        'referral_id' => $referral->id,
                        'icd10_code_id' => $icd10Code->id,
                        'code' => $icd10Code->code, // Keep for backward compatibility
                    ]);
                }
            }
        }

        $this->command->info('30 referrals with ICD-10 codes seeded successfully!');
    }
}
