<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('view-departments');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('view-departments');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('create-departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('update-departments');
    }

    public function delete(User $user, Department $department): bool
    {
        // Prevent deletion if department is in use
        if ($department->referrals()->count() > 0 || $department->staff()->count() > 0) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermissionTo('delete-departments');
    }
}
