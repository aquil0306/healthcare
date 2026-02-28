<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        // Get a random active department
        $department = Department::where('is_active', true)->inRandomOrder()->first();
        $departmentId = $department ? $department->id : null;

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'department' => $department ? $department->name : null,
            'department_id' => $departmentId,
            'is_available' => $this->faker->boolean(80), // 80% chance of being available
        ];
    }

    /**
     * Indicate that the staff should have an admin role (via Spatie)
     */
    public function admin(): static
    {
        return $this->afterCreating(function (Staff $staff) {
            $role = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
            if ($role) {
                $staff->user->assignRole($role);
            }
        });
    }

    /**
     * Indicate that the staff should have a doctor role (via Spatie)
     */
    public function doctor(): static
    {
        return $this->afterCreating(function (Staff $staff) {
            $role = \Spatie\Permission\Models\Role::where('name', 'doctor')->first();
            if ($role) {
                $staff->user->assignRole($role);
            }
        });
    }

    /**
     * Indicate that the staff should have a coordinator role (via Spatie)
     */
    public function coordinator(): static
    {
        return $this->afterCreating(function (Staff $staff) {
            $role = \Spatie\Permission\Models\Role::where('name', 'coordinator')->first();
            if ($role) {
                $staff->user->assignRole($role);
            }
        });
    }
}
