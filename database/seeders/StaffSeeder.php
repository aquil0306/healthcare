<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@healthcare.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        $adminStaff = Staff::updateOrCreate(
            ['email' => 'admin@healthcare.com'],
            [
                'user_id' => $adminUser->id,
                'name' => 'Admin User',
                'role' => 'admin',
                'department' => null,
                'is_available' => true,
            ]
        );

        // Assign Spatie admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
        }

        // Create Doctors
        $departments = ['cardiology', 'neurology', 'orthopedics', 'general'];
        $roles = ['doctor', 'coordinator'];

        foreach ($departments as $index => $department) {
            // Doctor
            $doctorEmail = "doctor.{$department}@healthcare.com";
            $doctorUser = User::updateOrCreate(
                ['email' => $doctorEmail],
                [
                    'name' => "Dr. {$department} Doctor",
                    'password' => Hash::make('password'),
                ]
            );

            $doctorStaff = Staff::updateOrCreate(
                ['email' => $doctorEmail],
                [
                    'user_id' => $doctorUser->id,
                    'name' => "Dr. {$department} Doctor",
                    'role' => 'doctor',
                    'department' => $department,
                    'is_available' => true,
                ]
            );

            // Assign Spatie doctor role
            $doctorRole = Role::where('name', 'doctor')->first();
            if ($doctorRole) {
                $doctorUser->assignRole($doctorRole);
            }

            // Coordinator
            $coordinatorEmail = "coordinator.{$department}@healthcare.com";
            $coordinatorUser = User::updateOrCreate(
                ['email' => $coordinatorEmail],
                [
                    'name' => "{$department} Coordinator",
                    'password' => Hash::make('password'),
                ]
            );

            $coordinatorStaff = Staff::updateOrCreate(
                ['email' => $coordinatorEmail],
                [
                    'user_id' => $coordinatorUser->id,
                    'name' => "{$department} Coordinator",
                    'role' => 'coordinator',
                    'department' => $department,
                    'is_available' => true,
                ]
            );

            // Assign Spatie coordinator role
            $coordinatorRole = Role::where('name', 'coordinator')->first();
            if ($coordinatorRole) {
                $coordinatorUser->assignRole($coordinatorRole);
            }
        }

        $this->command->info('Staff seeded successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('  Admin: admin@healthcare.com / password');
        $this->command->info('  Doctors: doctor.{department}@healthcare.com / password');
        $this->command->info('  Coordinators: coordinator.{department}@healthcare.com / password');
    }
}
