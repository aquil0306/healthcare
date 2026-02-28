<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('patients.viewAny');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->isAdmin() || $user->can('patients.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('patients.create');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->isAdmin() || $user->can('patients.update');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin() || $user->can('patients.delete');
    }
}

