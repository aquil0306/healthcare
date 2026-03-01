<?php

namespace Database\Seeders;

use App\Models\Department;
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
                'department' => null,
                'department_id' => null,
                'is_available' => true,
            ]
        );

        // Assign Spatie admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
        }

        // Map department names to Department model names
        $departmentMapping = [
            'cardiology' => 'Cardiology',
            'neurology' => 'Neurology',
            'orthopedics' => 'Orthopedics',
            'general' => 'Internal Medicine', // Map 'general' to 'Internal Medicine'
        ];

        // Create Doctors and Coordinators
        $departments = ['cardiology', 'neurology', 'orthopedics', 'general'];

        foreach ($departments as $departmentKey) {
            // Find the department by name
            $departmentName = $departmentMapping[$departmentKey] ?? ucfirst($departmentKey);
            $department = Department::where('name', $departmentName)->first();

            if (! $department) {
                $this->command->warn("Department '{$departmentName}' not found. Skipping staff creation for {$departmentKey}.");

                continue;
            }

            // Doctor
            $doctorEmail = "doctor.{$departmentKey}@healthcare.com";
            $doctorUser = User::updateOrCreate(
                ['email' => $doctorEmail],
                [
                    'name' => "Dr. {$departmentName} Doctor",
                    'password' => Hash::make('password'),
                ]
            );

            $doctorStaff = Staff::updateOrCreate(
                ['email' => $doctorEmail],
                [
                    'user_id' => $doctorUser->id,
                    'name' => "Dr. {$departmentName} Doctor",
                    'department' => $department->name,
                    'department_id' => $department->id,
                    'is_available' => true,
                ]
            );

            // Assign Spatie doctor role
            $doctorRole = Role::where('name', 'doctor')->first();
            if ($doctorRole) {
                $doctorUser->assignRole($doctorRole);
            }

            // Coordinator
            $coordinatorEmail = "coordinator.{$departmentKey}@healthcare.com";
            $coordinatorUser = User::updateOrCreate(
                ['email' => $coordinatorEmail],
                [
                    'name' => "{$departmentName} Coordinator",
                    'password' => Hash::make('password'),
                ]
            );

            $coordinatorStaff = Staff::updateOrCreate(
                ['email' => $coordinatorEmail],
                [
                    'user_id' => $coordinatorUser->id,
                    'name' => "{$departmentName} Coordinator",
                    'department' => $department->name,
                    'department_id' => $department->id,
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
