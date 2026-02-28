<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignReferralRequest;
use App\Http\Requests\CancelReferralRequest;
use App\Http\Resources\ReferralResource;
use App\Models\Referral;
use App\Repositories\ReferralRepository;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing referrals"
 * )
 */
class AdminReferralController extends Controller
{
    public function __construct(
        private ReferralRepository $referralRepository,
        private AuditService $auditService,
        private NotificationService $notificationService
    ) {
        //
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/referrals",
     *     operationId="listReferrals",
     *     tags={"Admin"},
     *     summary="List all referrals",
     *     description="Get a paginated list of all referrals with optional filtering",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="string", enum={"submitted", "assigned", "acknowledged", "completed", "cancelled"}, example="submitted")),
     *     @OA\Parameter(name="urgency", in="query", description="Filter by urgency level", @OA\Schema(type="string", enum={"routine", "urgent", "emergency"}, example="urgent")),
     *     @OA\Parameter(name="department", in="query", description="Filter by department name", @OA\Schema(type="string", example="cardiology")),
     *     @OA\Parameter(name="date_from", in="query", description="Filter referrals from this date (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2024-01-01")),
     *     @OA\Parameter(name="date_to", in="query", description="Filter referrals until this date (YYYY-MM-DD)", @OA\Schema(type="string", format="date", example="2024-12-31")),
     *     @OA\Parameter(name="page", in="query", description="Page number for pagination", @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of referrals retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(ref="#/components/schemas/Referral")
     *             ),
     *
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=150)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if (! \Illuminate\Support\Facades\Gate::allows('viewAny', Referral::class)) {
            abort(403, 'This action is unauthorized.');
        }

        $filters = $request->only(['status', 'urgency', 'department', 'date_from', 'date_to']);
        $referrals = $this->referralRepository->paginateWithFilters($filters, 15);

        return ReferralResource::collection($referrals)->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/referrals/{referral}",
     *     operationId="getReferral",
     *     tags={"Admin"},
     *     summary="View referral details",
     *     description="Get detailed information about a specific referral including patient, hospital, department, assigned staff, ICD-10 codes, audit logs, and AI triage information",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="referral", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral details retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Referral"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Referral not found")
     * )
     */
    public function show(Referral $referral): JsonResponse
    {
        if (! \Illuminate\Support\Facades\Gate::allows('view', $referral)) {
            abort(403, 'This action is unauthorized.');
        }

        $referral->load(['patient', 'hospital', 'department', 'assignedStaff', 'icd10Codes.icd10Code', 'auditLogs.user', 'aiTriageLog']);

        return (new ReferralResource($referral))->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/referrals/{referral}/assign",
     *     operationId="assignReferral",
     *     tags={"Admin"},
     *     summary="Assign referral to staff",
     *     description="Assign a referral to a specific staff member. The referral status will be updated to 'assigned' and the staff member will be notified.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="referral", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Assignment data",
     *
     *         @OA\JsonContent(
     *             required={"staff_id"},
     *
     *             @OA\Property(property="staff_id", type="integer", example=5, description="ID of the staff member to assign the referral to")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral assigned successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral assigned successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="assigned_staff_id", type="integer", example=5),
     *                 @OA\Property(property="status", type="string", example="assigned"),
     *                 @OA\Property(
     *                     property="assigned_staff",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="first_name", type="string", example="Jane"),
     *                     @OA\Property(property="last_name", type="string", example="Smith")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Referral or staff member not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The staff_id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="staff_id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The staff_id field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function assign(AssignReferralRequest $request, Referral $referral): JsonResponse
    {
        $oldStaffId = $referral->assigned_staff_id;
        $oldStatus = $referral->status;

        $referral->update([
            'assigned_staff_id' => $request->staff_id,
            'status' => 'assigned',
        ]);

        // Log the staff assignment change
        $this->auditService->logChange(
            $referral,
            'assigned',
            'assigned_staff_id',
            $oldStaffId,
            $request->staff_id
        );

        // Log the status change if it changed
        if ($oldStatus !== 'assigned') {
            $this->auditService->logChange(
                $referral,
                'status_changed',
                'status',
                $oldStatus,
                'assigned'
            );
        }

        // Notify the assigned staff member
        $assignedStaff = \App\Models\Staff::find($request->staff_id);
        if ($assignedStaff) {
            $this->notificationService->notifyStaffOfAssignment($assignedStaff, $referral->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Referral assigned successfully',
            'data' => $referral->fresh(['assignedStaff']),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/referrals/{referral}/cancel",
     *     operationId="cancelReferral",
     *     tags={"Admin"},
     *     summary="Cancel a referral",
     *     description="Cancel a referral that has not been completed. The cancellation reason is required and will be logged in the audit trail.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="referral", in="path", required=true, description="Referral ID", @OA\Schema(type="integer", example=123)),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Cancellation data",
     *
     *         @OA\JsonContent(
     *             required={"reason"},
     *
     *             @OA\Property(property="reason", type="string", example="Patient cancelled appointment", description="Reason for cancellation")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Referral cancelled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Referral cancelled successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="status", type="string", example="cancelled"),
     *                 @OA\Property(property="cancellation_reason", type="string", example="Patient cancelled appointment")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or cannot cancel",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot cancel a completed referral")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Referral not found")
     * )
     */
    public function cancel(CancelReferralRequest $request, Referral $referral): JsonResponse
    {
        if (! $referral->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed referral',
            ], 422);
        }

        $oldStatus = $referral->status;

        $referral->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
        ]);

        $this->auditService->logChange(
            $referral,
            'cancelled',
            'status',
            $oldStatus,
            'cancelled',
            ['reason' => $request->reason]
        );

        return response()->json([
            'success' => true,
            'message' => 'Referral cancelled successfully',
            'data' => $referral->fresh(),
        ]);
    }
}
