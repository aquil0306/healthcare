<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing patients"
 * )
 */
class AdminPatientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/patients",
     *     operationId="listPatients",
     *     tags={"Admin"},
     *     summary="List all patients",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        $query = Patient::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = strtolower($request->search);
            // Since encrypted fields can't be searched efficiently in SQL,
            // we'll load all patients and filter in memory
            // Note: For production, consider adding hash index columns for searchable encrypted fields
            $allPatients = Patient::with('referrals')->get();
            $filtered = $allPatients->filter(function ($patient) use ($search) {
                return str_contains(strtolower($patient->first_name), $search) ||
                       str_contains(strtolower($patient->last_name), $search) ||
                       str_contains(strtolower($patient->national_id), $search) ||
                       str_contains(strtolower($patient->insurance_number), $search);
            });

            // Manual pagination
            $page = $request->input('page', 1);
            $perPage = 15;
            $total = $filtered->count();
            $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

            $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
                PatientResource::collection($items),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return PatientResource::collection($paginated)->additional([
                'success' => true,
            ])->response();
        }

        $patients = $query->with('referrals')->orderBy('created_at', 'desc')->paginate(15);

        return PatientResource::collection($patients)->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/patients/{id}",
     *     operationId="getPatient",
     *     tags={"Admin"},
     *     summary="View patient details",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);

        $patient->load(['referrals.hospital', 'referrals.assignedStaff', 'referrals.icd10Codes']);

        return (new PatientResource($patient))->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/patients",
     *     operationId="createPatient",
     *     tags={"Admin"},
     *     summary="Create a new patient",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "date_of_birth", "national_id", "insurance_number"},
     *
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="national_id", type="string"),
     *             @OA\Property(property="insurance_number", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return (new PatientResource($patient))->additional([
            'success' => true,
            'message' => 'Patient created successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/patients/{id}",
     *     operationId="updatePatient",
     *     tags={"Admin"},
     *     summary="Update a patient",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="national_id", type="string"),
     *             @OA\Property(property="insurance_number", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return (new PatientResource($patient->fresh()))->additional([
            'success' => true,
            'message' => 'Patient updated successfully',
        ])->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/patients/{id}",
     *     operationId="deletePatient",
     *     tags={"Admin"},
     *     summary="Delete a patient",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $this->authorize('delete', $patient);

        if ($patient->referrals()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete patient with existing referrals',
            ], 422);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient deleted successfully',
        ]);
    }
}
