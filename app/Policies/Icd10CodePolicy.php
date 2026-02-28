<?php

namespace App\Policies;

use App\Models\Icd10Code;
use App\Models\User;

class Icd10CodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('view-icd10-codes');
    }

    public function view(User $user, Icd10Code $icd10Code): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('view-icd10-codes');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('create-icd10-codes');
    }

    public function update(User $user, Icd10Code $icd10Code): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('update-icd10-codes');
    }

    public function delete(User $user, Icd10Code $icd10Code): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('delete-icd10-codes');
    }
}
