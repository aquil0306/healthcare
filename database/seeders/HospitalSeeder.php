<?php

namespace Database\Seeders;

use App\Models\Hospital;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        $hospitals = [
            [
                'name' => 'City General Hospital',
                'code' => 'CGH001',
                'status' => 'active',
                'api_key' => Str::random(64),
            ],
            [
                'name' => 'Regional Medical Center',
                'code' => 'RMC002',
                'status' => 'active',
                'api_key' => Str::random(64),
            ],
            [
                'name' => 'Community Health Hospital',
                'code' => 'CHH003',
                'status' => 'active',
                'api_key' => Str::random(64),
            ],
        ];

        foreach ($hospitals as $hospital) {
            Hospital::updateOrCreate(
                ['code' => $hospital['code']],
                $hospital
            );
        }

        $this->command->info('Hospitals seeded successfully!');
        $this->command->info('API Keys:');
        foreach (Hospital::all() as $hospital) {
            $this->command->info("  {$hospital->name} ({$hospital->code}): {$hospital->api_key}");
        }
    }
}
