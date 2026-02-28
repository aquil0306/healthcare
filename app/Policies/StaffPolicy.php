<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('staff.viewAny');
    }

    public function view(User $user, Staff $staff): bool
    {
        // Users can view their own staff record
        if ($user->staff?->id === $staff->id) {
            return true;
        }

        return $user->isAdmin() || $user->can('staff.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('staff.create');
    }

    public function update(User $user, Staff $staff): bool
    {
        // Users can update their own staff record (limited fields)
        if ($user->staff?->id === $staff->id) {
            return true;
        }

        return $user->isAdmin() || $user->can('staff.update');
    }

    public function delete(User $user, Staff $staff): bool
    {
        // Cannot delete own account
        if ($user->staff?->id === $staff->id) {
            return false;
        }

        return $user->isAdmin() || $user->can('staff.delete');
    }

    public function assignRole(User $user): bool
    {
        return $user->isAdmin() || $user->can('staff.assign-role');
    }

    public function assignPermission(User $user): bool
    {
        return $user->isAdmin() || $user->can('staff.assign-permission');
    }
}
