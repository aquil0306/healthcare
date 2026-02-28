<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Referral $referral): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Staff can view their assigned referrals
        return $referral->assigned_staff_id === $user->staff?->id;
    }

    public function create(User $user): bool
    {
        // Only hospitals can create referrals via API key
        return false;
    }

    public function update(User $user, Referral $referral): bool
    {
        return $user->isAdmin();
    }

    public function assign(User $user): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, Referral $referral): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $referral->canBeCancelled();
    }

    public function acknowledge(User $user, Referral $referral): bool
    {
        return $referral->assigned_staff_id === $user->staff?->id;
    }
}
