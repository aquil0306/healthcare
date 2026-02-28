<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        // Admins can view all notifications, staff can view their own
        return $user->isAdmin() || true;
    }

    public function view(User $user, Notification $notification): bool
    {
        // Admins can view any notification, staff can only view their own
        return $user->isAdmin() || $notification->staff_id === $user->staff?->id;
    }

    public function acknowledge(User $user, Notification $notification): bool
    {
        return $notification->staff_id === $user->staff?->id;
    }
}
