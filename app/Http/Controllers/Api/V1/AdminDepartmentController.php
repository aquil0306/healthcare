<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminDepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!Gate::allows('viewAny', Department::class)) {
            abort(403, 'This action is unauthorized.');
        }

        $query = Department::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $departments = $query->orderBy('name')->paginate(15);

        return DepartmentResource::collection($departments)->additional([
            'success' => true,
        ])->response();
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        return (new DepartmentResource($department))->additional([
            'success' => true,
        ])->response();
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create($request->validated());

        return (new DepartmentResource($department))->additional([
            'success' => true,
            'message' => 'Department created successfully',
        ])->response()->setStatusCode(201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department->update($request->validated());

        return (new DepartmentResource($department->fresh()))->additional([
            'success' => true,
            'message' => 'Department updated successfully',
        ])->response();
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        if ($department->referrals()->count() > 0 || $department->staff()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department that is in use',
            ], 422);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully',
        ]);
    }

    /**
     * Get ICD-10 codes for a department
     */
    public function getIcd10Codes(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        $department->load('icd10Codes');

        return response()->json([
            'success' => true,
            'data' => $department->icd10Codes->map(function ($code) {
                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'description' => $code->description,
                    'category' => $code->category,
                    'priority' => $code->pivot->priority,
                    'is_primary' => $code->pivot->is_primary,
                    'notes' => $code->pivot->notes,
                ];
            }),
        ]);
    }

    /**
     * Sync ICD-10 codes for a department
     */
    public function syncIcd10Codes(Request $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $validated = $request->validate([
            'icd10_codes' => 'required|array',
            'icd10_codes.*.id' => 'required|exists:icd10_codes,id',
            'icd10_codes.*.priority' => 'nullable|integer|min:1|max:10',
            'icd10_codes.*.is_primary' => 'nullable|boolean',
            'icd10_codes.*.notes' => 'nullable|string|max:500',
        ]);

        $syncData = [];
        foreach ($validated['icd10_codes'] as $mapping) {
            $syncData[$mapping['id']] = [
                'priority' => $mapping['priority'] ?? 1,
                'is_primary' => $mapping['is_primary'] ?? false,
                'notes' => $mapping['notes'] ?? null,
            ];
        }

        $department->icd10Codes()->sync($syncData);

        return response()->json([
            'success' => true,
            'message' => 'ICD-10 codes updated successfully',
            'data' => $department->fresh('icd10Codes'),
        ]);
    }
}
