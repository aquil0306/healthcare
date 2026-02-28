<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Referral;
use App\Repositories\ReferralRepository;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Staff",
 *     description="Staff API endpoints for managing assigned referrals"
 * )
 */
class StaffReferralController extends Controller
{
    public function __construct(
        private ReferralRepository $referralRepository,
        private AuditService $auditService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/staff/referrals",
     *     operationId="listStaffReferrals",
     *     tags={"Staff"},
     *     summary="List referrals assigned to staff",
     *     description="Get a list of all referrals assigned to the currently authenticated staff member",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of assigned referrals retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Referral")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Staff access required")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $staffId = $request->user()->staff->id;
        $referrals = $this->referralRepository->getAssignedToStaff($staffId)
            ->with(['patient', 'hospital', 'department', 'icd10Codes.icd10Code'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ReferralResource::collection($referrals)->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/staff/referrals/{id}/acknowledge",
     *     operationId="acknowledgeReferral",
     *     tags={"Staff"},
     *     summary="Acknowledge a referral",
     *     description="Acknowledge a referral assigned to the current staff member. The referral status will be updated to 'acknowledged' and the acknowledgment timestamp will be recorded.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral acknowledged successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral acknowledged"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="status", type="string", example="acknowledged"),
     *                 @OA\Property(property="acknowledged_at", type="string", format="date-time", example="2024-01-15T10:30:00Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Referral not assigned to this staff member",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Referral not found")
     * )
     */
    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $referral = $this->referralRepository->find($id);
        $staffId = $request->user()->staff->id;

        if ($referral->assigned_staff_id !== $staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $oldStatus = $referral->status;

        $referral->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);

        // Log the status change
        $this->auditService->logChange(
            $referral,
            'acknowledged',
            'status',
            $oldStatus,
            'acknowledged'
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral acknowledged',
            'data' => $referral->fresh(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/staff/referrals/{id}",
     *     operationId="getStaffReferral",
     *     tags={"Staff"},
     *     summary="View referral details",
     *     description="Get detailed information about a specific referral assigned to the current staff member",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral details retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Referral")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Referral not assigned to this staff member"),
     *     @OA\Response(response=404, description="Referral not found")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $referral = $this->referralRepository->find($id);
        $staffId = $request->user()->staff->id;

        if ($referral->assigned_staff_id !== $staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $referral->load(['patient.referrals', 'hospital', 'department', 'assignedStaff', 'icd10Codes.icd10Code', 'auditLogs.user', 'aiTriageLog']);

        return (new ReferralResource($referral))->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/staff/referrals/{id}/complete",
     *     operationId="completeReferral",
     *     tags={"Staff"},
     *     summary="Mark referral as complete",
     *     description="Mark a referral assigned to the current staff member as completed",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral marked as complete",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral marked as complete"),
     *             @OA\Property(property="data", ref="#/components/schemas/Referral")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Referral not assigned to this staff member"),
     *     @OA\Response(response=404, description="Referral not found")
     * )
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $referral = $this->referralRepository->find($id);
        $staffId = $request->user()->staff->id;

        if ($referral->assigned_staff_id !== $staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($referral->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Referral is already completed',
            ], 422);
        }

        $oldStatus = $referral->status;

        $referral->update([
            'status' => 'completed',
        ]);

        // Log the status change
        $this->auditService->logChange(
            $referral,
            'completed',
            'status',
            $oldStatus,
            'completed'
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral marked as complete',
            'data' => $referral->fresh(['patient', 'hospital', 'department']),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/staff/referrals/{id}/update-status",
     *     operationId="updateReferralStatus",
     *     tags={"Staff"},
     *     summary="Update referral status",
     *     description="Update the status of a referral assigned to the current staff member",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"status"},
     *
     *             @OA\Property(property="status", type="string", enum={"acknowledged", "in_progress", "completed"}, example="in_progress")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral status updated successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral status updated"),
     *             @OA\Property(property="data", ref="#/components/schemas/Referral")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Referral not assigned to this staff member"),
     *     @OA\Response(response=404, description="Referral not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:acknowledged,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $referral = $this->referralRepository->find($id);
        $staffId = $request->user()->staff->id;

        if ($referral->assigned_staff_id !== $staffId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $oldStatus = $referral->status;
        $newStatus = $request->input('status');

        if ($oldStatus === $newStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Referral is already in this status',
            ], 422);
        }

        $updateData = ['status' => $newStatus];

        // Set acknowledged_at if status is acknowledged
        if ($newStatus === 'acknowledged' && ! $referral->acknowledged_at) {
            $updateData['acknowledged_at'] = now();
        }

        $referral->update($updateData);

        // Log the status change
        $this->auditService->logChange(
            $referral,
            'status_changed',
            'status',
            $oldStatus,
            $newStatus
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral status updated',
            'data' => $referral->fresh(['patient', 'hospital', 'department']),
        ]);
    }
}
