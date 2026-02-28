<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Notification management endpoints"
 * )
 */
class NotificationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/notifications",
     *     operationId="listNotifications",
     *     tags={"Notifications"},
     *     summary="List notifications for authenticated user",
     *     description="Get a paginated list of notifications for the currently authenticated staff member",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="page", in="query", description="Page number for pagination", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Notifications retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notifications retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="referral_assigned"),
     *                         @OA\Property(property="message", type="string", example="New referral assigned to you"),
     *                         @OA\Property(property="is_read", type="boolean", example=false),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=45)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has a staff record
        if (!$user->staff) {
            return response()->json([
                'success' => true,
                'message' => 'No staff record found for this user',
                'data' => [
                    'data' => [],
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                ],
            ]);
        }

        $staffId = $user->staff->id;
        $notifications = Notification::where('staff_id', $staffId)
            ->with(['referral'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $response = [
            'success' => true,
            'message' => $notifications->total() === 0 
                ? 'No notifications found. You will receive notifications when referrals are assigned to you or when your department receives new referrals.' 
                : 'Notifications retrieved successfully',
            'data' => $notifications,
        ];

        return response()->json($response);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notifications/{notification}/acknowledge",
     *     operationId="acknowledgeNotification",
     *     tags={"Notifications"},
     *     summary="Acknowledge a notification",
     *     description="Mark a notification as read/acknowledged",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="notification", in="path", required=true, description="Notification ID", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="Notification acknowledged successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Notification acknowledged"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="is_read", type="boolean", example=true),
     *                 @OA\Property(property="read_at", type="string", format="date-time", example="2024-01-15T10:35:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Notification does not belong to user"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function acknowledge(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('acknowledge', $notification);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification acknowledged',
            'data' => $notification->fresh(),
        ]);
    }
}
