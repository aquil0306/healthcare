<?php

namespace App\Policies;

use App\Models\Hospital;
use App\Models\User;

class HospitalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('hospitals.viewAny');
    }

    public function view(User $user, Hospital $hospital): bool
    {
        return $user->isAdmin() || $user->can('hospitals.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('hospitals.create');
    }

    public function update(User $user, Hospital $hospital): bool
    {
        return $user->isAdmin() || $user->can('hospitals.update');
    }

    public function delete(User $user, Hospital $hospital): bool
    {
        return $user->isAdmin() || $user->can('hospitals.delete');
    }

    public function regenerateApiKey(User $user, Hospital $hospital): bool
    {
        return $user->isAdmin() || $user->can('hospitals.regenerate-api-key');
    }
}
