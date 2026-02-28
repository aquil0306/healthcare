<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class, // Must be first to create roles/permissions
            DepartmentSeeder::class, // Must be before Staff and Referrals
            Icd10CodeSeeder::class, // Must be before Referrals
            HospitalSeeder::class,
            StaffSeeder::class,
            PatientSeeder::class,
            ReferralSeeder::class,
        ]);

        $this->command->info('Database seeding completed!');
    }
}
