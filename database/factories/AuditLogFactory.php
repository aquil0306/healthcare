<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actions = ['created', 'updated', 'status_changed', 'assigned', 'cancelled', 'acknowledged'];
        $action = $this->faker->randomElement($actions);
        
        return [
            'referral_id' => Referral::factory(),
            'user_id' => User::factory(),
            'action' => $action,
            'field' => $this->faker->randomElement(['status', 'urgency', 'department', 'assigned_staff_id', null]),
            'old_value' => $this->faker->optional()->word(),
            'new_value' => $this->faker->optional()->word(),
            'metadata' => $this->faker->optional()->randomElement([
                ['reason' => 'Patient request'],
                ['escalated_at' => now()->toIso8601String()],
                null,
            ]),
        ];
    }
}
