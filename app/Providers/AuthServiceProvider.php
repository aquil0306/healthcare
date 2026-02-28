<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Hospital;
use App\Models\Icd10Code;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\HospitalPolicy;
use App\Policies\Icd10CodePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\ReferralPolicy;
use App\Policies\StaffPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Hospital::class => HospitalPolicy::class,
        Patient::class => PatientPolicy::class,
        Referral::class => ReferralPolicy::class,
        Staff::class => StaffPolicy::class,
        Icd10Code::class => Icd10CodePolicy::class,
        Department::class => DepartmentPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Notification::class => NotificationPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \Spatie\Permission\Models\Permission::class => \App\Policies\PermissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for common operations
        \Illuminate\Support\Facades\Gate::define('manage-hospitals', function (User $user) {
            return $user->isAdmin() || $user->hasPermissionTo('hospitals.manage');
        });

        \Illuminate\Support\Facades\Gate::define('manage-patients', function (User $user) {
            return $user->isAdmin() || $user->hasPermissionTo('patients.manage');
        });

        \Illuminate\Support\Facades\Gate::define('manage-staff', function (User $user) {
            return $user->isAdmin() || $user->hasPermissionTo('staff.manage');
        });

        \Illuminate\Support\Facades\Gate::define('manage-roles', function (User $user) {
            return $user->isAdmin() || $user->hasPermissionTo('roles.manage');
        });

        \Illuminate\Support\Facades\Gate::define('manage-permissions', function (User $user) {
            return $user->isAdmin() || $user->hasPermissionTo('permissions.manage');
        });
    }
}
