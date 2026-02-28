<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Referral;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin reporting endpoints"
 * )
 */
class ReportingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/reports/statistics",
     *     operationId="getStatistics",
     *     tags={"Admin"},
     *     summary="Get aggregated reporting statistics",
     *     description="Get comprehensive statistics about hospitals, patients, staff, referrals, and system metrics. Date filters apply to referral trends only.",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="date_from", in="query", description="Start date for referral statistics (YYYY-MM-DD). Defaults to 30 days ago.", @OA\Schema(type="string", format="date", example="2024-01-01")),
     *     @OA\Parameter(name="date_to", in="query", description="End date for referral statistics (YYYY-MM-DD). Defaults to today.", @OA\Schema(type="string", format="date", example="2024-01-31")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_hospitals", type="integer", example=10),
     *                 @OA\Property(property="active_hospitals", type="integer", example=9),
     *                 @OA\Property(property="suspended_hospitals", type="integer", example=1),
     *                 @OA\Property(property="total_patients", type="integer", example=500),
     *                 @OA\Property(property="total_staff", type="integer", example=50),
     *                 @OA\Property(
     *                     property="staff_by_role",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="role", type="string", example="doctor"),
     *                         @OA\Property(property="count", type="integer", example=30)
     *                     )
     *                 ),
     *                 @OA\Property(property="available_staff", type="integer", example=45),
     *                 @OA\Property(property="unavailable_staff", type="integer", example=5),
     *                 @OA\Property(property="total_referrals", type="integer", example=1000),
     *                 @OA\Property(
     *                     property="by_urgency",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="urgency", type="string", example="urgent"),
     *                         @OA\Property(property="count", type="integer", example=300)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_status",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="status", type="string", example="assigned"),
     *                         @OA\Property(property="count", type="integer", example=200)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_department",
     *                     type="array",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="department", type="string", example="cardiology"),
     *                         @OA\Property(property="count", type="integer", example=150)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="referrals_per_day",
     *                     type="array",
     *                     description="Referral counts per day (filtered by date range)",
     *
     *                     @OA\Items(
     *                         type="object",
     *
     *                         @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                         @OA\Property(property="count", type="integer", example=25)
     *                     )
     *                 ),
     *                 @OA\Property(property="average_ai_confidence", type="number", format="float", example=0.92, description="Average AI confidence score (0-1)"),
     *                 @OA\Property(property="escalation_rate", type="number", format="float", example=5.5, description="Percentage of emergency referrals that were escalated"),
     *                 @OA\Property(property="cancellation_rate", type="number", format="float", example=2.3, description="Percentage of referrals that were cancelled")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        // Ensure date_to includes the full day (end of day)
        $dateToEndOfDay = $dateTo.' 23:59:59';

        $dateFilteredQuery = Referral::whereBetween('created_at', [$dateFrom.' 00:00:00', $dateToEndOfDay]);
        $allReferralsQuery = Referral::query();

        $stats = [
            // Entity counts (always total, not filtered by date)
            'total_hospitals' => Hospital::count(),
            'active_hospitals' => Hospital::where('status', 'active')->count(),
            'suspended_hospitals' => Hospital::where('status', 'suspended')->count(),
            'total_patients' => Patient::count(),
            'total_staff' => Staff::count(),
            'staff_by_role' => Staff::with('user.roles')
                ->get()
                ->groupBy(function ($staff) {
                    return $staff->user?->roles->first()?->name ?? 'no-role';
                })
                ->map(function ($group, $roleName) {
                    return ['role' => $roleName, 'count' => $group->count()];
                })
                ->values(),
            'available_staff' => Staff::where('is_available', true)->count(),
            'unavailable_staff' => Staff::where('is_available', false)->count(),

            // Referral statistics - total counts (all time)
            'total_referrals' => $allReferralsQuery->count(),
            'by_urgency' => $allReferralsQuery->selectRaw('LOWER(TRIM(urgency)) as urgency, COUNT(*) as count')
                ->groupBy(DB::raw('LOWER(TRIM(urgency))'))
                ->get()
                ->map(function ($item) {
                    return [
                        'urgency' => strtolower(trim($item->urgency)),
                        'count' => (int) $item->count,
                    ];
                })
                ->values(),
            'by_status' => $allReferralsQuery->selectRaw('LOWER(TRIM(status)) as status, COUNT(*) as count')
                ->groupBy(DB::raw('LOWER(TRIM(status))'))
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => strtolower(trim($item->status)),
                        'count' => (int) $item->count,
                    ];
                })
                ->values(),
            'by_department' => $allReferralsQuery->selectRaw('department, COUNT(*) as count')
                ->whereNotNull('department')
                ->groupBy('department')
                ->get()
                ->map(function ($item) {
                    return [
                        'department' => $item->department,
                        'count' => (int) $item->count,
                    ];
                }),

            // Date-filtered statistics for charts
            'referrals_per_day' => $dateFilteredQuery->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy(DB::raw('DATE(created_at)'), 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date ? date('Y-m-d', strtotime($item->date)) : null,
                        'count' => (int) $item->count,
                    ];
                })
                ->filter(function ($item) {
                    return $item['date'] !== null;
                })
                ->values(),
            'average_ai_confidence' => (function () use ($dateFilteredQuery) {
                $avg = $dateFilteredQuery->clone()->whereNotNull('ai_confidence_score')
                    ->avg('ai_confidence_score');

                // Return raw float (0-1), frontend will handle percentage conversion
                return $avg ? (float) $avg : 0.0;
            })(),
            'escalation_rate' => (function () use ($dateFilteredQuery) {
                // Escalation rate: percentage of emergency referrals that were escalated
                $emergencyReferrals = $dateFilteredQuery->clone()->where('urgency', 'emergency');
                $totalEmergency = $emergencyReferrals->count();
                if ($totalEmergency === 0) {
                    return 0.0;
                }

                $escalatedCount = $emergencyReferrals->clone()->whereHas('auditLogs', function ($q) {
                    $q->where('action', 'escalated');
                })->count();

                return (float) ($escalatedCount / $totalEmergency * 100);
            })(),
            'cancellation_rate' => (float) ($dateFilteredQuery->clone()->where('status', 'cancelled')
                ->count() / max($dateFilteredQuery->clone()->count(), 1) * 100),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
