<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Hospital permissions
            'hospitals.viewAny',
            'hospitals.view',
            'hospitals.create',
            'hospitals.update',
            'hospitals.delete',
            'hospitals.regenerate-api-key',
            'hospitals.manage',

            // Patient permissions
            'patients.viewAny',
            'patients.view',
            'patients.create',
            'patients.update',
            'patients.delete',
            'patients.manage',

            // Staff permissions
            'staff.viewAny',
            'staff.view',
            'staff.create',
            'staff.update',
            'staff.delete',
            'staff.assign-role',
            'staff.assign-permission',
            'staff.manage',

            // Referral permissions
            'referrals.viewAny',
            'referrals.view',
            'referrals.create',
            'referrals.update',
            'referrals.assign',
            'referrals.cancel',
            'referrals.acknowledge',
            'referrals.manage',

            // Role permissions
            'roles.viewAny',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.manage',

            // Permission permissions
            'permissions.viewAny',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'permissions.manage',

            // Notification permissions
            'notifications.viewAny',
            'notifications.view',
            'notifications.acknowledge',
            'notifications.manage',

            // Report permissions
            'reports.view',
            'reports.manage',

            // ICD-10 Code permissions
            'icd10-codes.viewAny',
            'icd10-codes.view',
            'icd10-codes.create',
            'icd10-codes.update',
            'icd10-codes.delete',
            'icd10-codes.manage',

            // Department permissions
            'departments.viewAny',
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',
            'departments.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $doctorRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctorRole->givePermissionTo([
            'referrals.viewAny',
            'referrals.view',
            'referrals.acknowledge',
            'patients.view',
            'notifications.viewAny',
            'notifications.view',
            'notifications.acknowledge',
        ]);

        $coordinatorRole = Role::firstOrCreate(['name' => 'coordinator', 'guard_name' => 'web']);
        $coordinatorRole->givePermissionTo([
            'referrals.viewAny',
            'referrals.view',
            'referrals.acknowledge',
            'patients.view',
            'notifications.viewAny',
            'notifications.view',
            'notifications.acknowledge',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Created roles: admin, doctor, coordinator');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}

