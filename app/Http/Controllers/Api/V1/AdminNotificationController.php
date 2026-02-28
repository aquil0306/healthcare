<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admins can view all notifications
        if (! Gate::allows('viewAny', Notification::class)) {
            abort(403, 'This action is unauthorized.');
        }

        $query = Notification::with(['staff', 'referral']);

        // Filter by staff_id
        if ($request->has('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by referral_id
        if ($request->has('referral_id')) {
            $query->where('referral_id', $request->referral_id);
        }

        // Filter by channel
        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('is_read')) {
            if ($request->boolean('is_read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        // Date range filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in message
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('message', 'like', "%{$search}%");
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return NotificationResource::collection($notifications)->additional([
            'success' => true,
        ])->response();
    }

    public function show(Notification $notification): JsonResponse
    {
        if (! Gate::allows('view', $notification)) {
            abort(403, 'This action is unauthorized.');
        }

        $notification->load(['staff', 'referral']);

        return (new NotificationResource($notification))->additional([
            'success' => true,
        ])->response();
    }
}
