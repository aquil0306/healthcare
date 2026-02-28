<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Referral;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'referral_id' => Referral::factory(),
            'message' => $this->faker->sentence(),
            'channel' => $this->faker->randomElement(['email', 'sms', 'in_app']),
            'sent_at' => now(),
            'read_at' => $this->faker->optional(0.3)->dateTime(), // 30% chance of being read
            'type' => $this->faker->randomElement(['referral', 'escalation', 'reminder']),
        ];
    }
}
