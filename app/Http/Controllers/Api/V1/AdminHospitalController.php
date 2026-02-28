<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHospitalRequest;
use App\Http\Requests\UpdateHospitalRequest;
use App\Http\Resources\HospitalResource;
use App\Models\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing hospitals"
 * )
 */
class AdminHospitalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/hospitals",
     *     operationId="listHospitals",
     *     tags={"Admin"},
     *     summary="List all hospitals",
     *     description="Get a paginated list of all hospitals with optional filtering by status",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="string", enum={"active", "suspended"}, example="active")),
     *     @OA\Parameter(name="page", in="query", description="Page number for pagination", @OA\Schema(type="integer", example=1)),
     *     @OA\Response(
     *         response=200,
     *         description="List of hospitals retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Hospital")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Hospital::class);

        $query = Hospital::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $hospitals = $query->orderBy('created_at', 'desc')->paginate(15);

        return HospitalResource::collection($hospitals)->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/hospitals/{hospital}",
     *     operationId="getHospital",
     *     tags={"Admin"},
     *     summary="View hospital details",
     *     description="Get detailed information about a specific hospital",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="hospital", in="path", required=true, description="Hospital ID", @OA\Schema(type="integer", example=2)),
     *     @OA\Response(
     *         response=200,
     *         description="Hospital details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Hospital")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Hospital not found")
     * )
     */
    public function show(Hospital $hospital): JsonResponse
    {
        $this->authorize('view', $hospital);

        $hospital->load('referrals');

        return (new HospitalResource($hospital))->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/hospitals",
     *     operationId="createHospital",
     *     tags={"Admin"},
     *     summary="Create a new hospital",
     *     description="Create a new hospital. An API key will be automatically generated and returned in the response.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Hospital data",
     *         @OA\JsonContent(
     *             required={"name", "code", "status"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="City General Hospital", description="Hospital name"),
     *             @OA\Property(property="code", type="string", maxLength=50, example="CGH001", description="Unique hospital code"),
     *             @OA\Property(property="status", type="string", enum={"active", "suspended"}, example="active", description="Hospital status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hospital created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hospital created successfully"),
     *             @OA\Property(property="api_key", type="string", example="abc123def456...", description="Generated API key for hospital authentication"),
     *             @OA\Property(property="data", ref="#/components/schemas/Hospital")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The code has already been taken."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="code",
     *                     type="array",
     *                     @OA\Items(type="string", example="The code has already been taken.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden - Admin access required")
     * )
     */
    public function store(StoreHospitalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $apiKey = Str::random(64);

        $hospital = Hospital::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'status' => $validated['status'],
            'api_key' => $apiKey,
        ]);

        return (new HospitalResource($hospital))->additional([
            'success' => true,
            'message' => 'Hospital created successfully',
            'api_key' => $apiKey, // Show API key only on creation
        ])->response()->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/hospitals/{hospital}",
     *     operationId="updateHospital",
     *     tags={"Admin"},
     *     summary="Update a hospital",
     *     description="Update hospital information. All fields are optional but at least one must be provided.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="hospital", in="path", required=true, description="Hospital ID", @OA\Schema(type="integer", example=2)),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Hospital update data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="City General Hospital Updated", description="Hospital name"),
     *             @OA\Property(property="code", type="string", maxLength=50, example="CGH001", description="Unique hospital code"),
     *             @OA\Property(property="status", type="string", enum={"active", "suspended"}, example="active", description="Hospital status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hospital updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hospital updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Hospital")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Hospital not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateHospitalRequest $request, Hospital $hospital): JsonResponse
    {
        $hospital->update($request->validated());

        return (new HospitalResource($hospital->fresh()))->additional([
            'success' => true,
            'message' => 'Hospital updated successfully',
        ])->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/hospitals/{hospital}",
     *     operationId="deleteHospital",
     *     tags={"Admin"},
     *     summary="Delete a hospital",
     *     description="Delete a hospital. This operation will fail if the hospital has existing referrals.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="hospital", in="path", required=true, description="Hospital ID", @OA\Schema(type="integer", example=2)),
     *     @OA\Response(
     *         response=200,
     *         description="Hospital deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hospital deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete hospital with existing referrals",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot delete hospital with existing referrals")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Hospital not found")
     * )
     */
    public function destroy(Hospital $hospital): JsonResponse
    {
        $this->authorize('delete', $hospital);

        if ($hospital->referrals()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete hospital with existing referrals',
            ], 422);
        }

        $hospital->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hospital deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/hospitals/{hospital}/regenerate-api-key",
     *     operationId="regenerateApiKey",
     *     tags={"Admin"},
     *     summary="Regenerate API key for a hospital",
     *     description="Generate a new API key for a hospital. The old API key will be invalidated immediately.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="hospital", in="path", required=true, description="Hospital ID", @OA\Schema(type="integer", example=2)),
     *     @OA\Response(
     *         response=200,
     *         description="API key regenerated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="API key regenerated successfully"),
     *             @OA\Property(property="api_key", type="string", example="new_abc123def456...", description="New API key")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Hospital not found")
     * )
     */
    public function regenerateApiKey(Hospital $hospital): JsonResponse
    {
        $this->authorize('regenerateApiKey', $hospital);

        $newApiKey = Str::random(64);
        $hospital->update(['api_key' => $newApiKey]);

        return response()->json([
            'success' => true,
            'message' => 'API key regenerated successfully',
            'api_key' => $newApiKey,
        ]);
    }
}

