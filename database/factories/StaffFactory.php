<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        $role = $this->faker->randomElement(['admin', 'doctor', 'coordinator']);
        $departments = ['cardiology', 'neurology', 'orthopedics', 'general', 'emergency'];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $role,
            'department' => $role === 'admin' ? null : $this->faker->randomElement($departments),
            'is_available' => $this->faker->boolean(80), // 80% chance of being available
        ];
    }
}
