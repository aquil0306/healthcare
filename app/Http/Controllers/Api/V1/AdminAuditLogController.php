<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only admins can view audit logs
        if (!Gate::allows('viewAny', AuditLog::class)) {
            abort(403, 'This action is unauthorized.');
        }

        $query = AuditLog::with(['user', 'referral']);

        // Filter by referral_id
        if ($request->has('referral_id')) {
            $query->where('referral_id', $request->referral_id);
        }

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by field
        if ($request->has('field')) {
            $query->where('field', $request->field);
        }

        // Date range filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in action, field, old_value, new_value
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('field', 'like', "%{$search}%")
                  ->orWhere('old_value', 'like', "%{$search}%")
                  ->orWhere('new_value', 'like', "%{$search}%");
            });
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate(20);

        return AuditLogResource::collection($auditLogs)->additional([
            'success' => true,
        ])->response();
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        if (!Gate::allows('view', $auditLog)) {
            abort(403, 'This action is unauthorized.');
        }

        $auditLog->load(['user', 'referral']);

        return (new AuditLogResource($auditLog))->additional([
            'success' => true,
        ])->response();
    }
}
