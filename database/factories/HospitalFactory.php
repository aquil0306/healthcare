<?php

namespace Database\Factories;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HospitalFactory extends Factory
{
    protected $model = Hospital::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Hospital',
            'code' => strtoupper(Str::random(6)),
            'status' => 'active',
            'api_key' => Str::random(64),
        ];
    }
}
