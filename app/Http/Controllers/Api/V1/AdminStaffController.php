<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignPermissionRequest;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing staff"
 * )
 */
class AdminStaffController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/staff",
     *     operationId="listStaff",
     *     tags={"Admin"},
     *     summary="List all staff",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="role", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="department", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Staff::class);

        $query = Staff::with(['user.roles', 'user.permissions']);

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        $staff = $query->orderBy('created_at', 'desc')->paginate(15);

        return StaffResource::collection($staff)->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/staff/{id}",
     *     operationId="getStaff",
     *     tags={"Admin"},
     *     summary="View staff details",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(Staff $staff): JsonResponse
    {
        $this->authorize('view', $staff);

        $staff->load(['user.roles', 'user.permissions', 'assignedReferrals.patient', 'assignedReferrals.hospital']);

        return (new StaffResource($staff))->additional([
            'success' => true,
        ])->response();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/staff",
     *     operationId="createStaff",
     *     tags={"Admin"},
     *     summary="Create a new staff member",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "role", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin", "doctor", "coordinator"}),
     *             @OA\Property(property="department", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="is_available", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Create user account
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create staff record
        $staff = Staff::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department' => $validated['department'] ?? null,
            'is_available' => $validated['is_available'] ?? true,
        ]);

        $staff->load('user');

        return (new StaffResource($staff))->additional([
            'success' => true,
            'message' => 'Staff member created successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/staff/{id}",
     *     operationId="updateStaff",
     *     tags={"Admin"},
     *     summary="Update a staff member",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin", "doctor", "coordinator"}),
     *             @OA\Property(property="department", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="is_available", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(UpdateStaffRequest $request, Staff $staff): JsonResponse
    {
        $validated = $request->validated();

        // Update user if name or email changed
        if (isset($validated['name']) || isset($validated['email'])) {
            $userData = [];
            if (isset($validated['name'])) {
                $userData['name'] = $validated['name'];
            }
            if (isset($validated['email'])) {
                $userData['email'] = $validated['email'];
            }
            $staff->user->update($userData);
        }

        // Update password if provided
        if (isset($validated['password'])) {
            $staff->user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        // Update staff record
        $staffData = array_filter([
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'] ?? null,
            'department' => $validated['department'] ?? null,
            'is_available' => $validated['is_available'] ?? null,
        ], fn($value) => $value !== null);

        $staff->update($staffData);

        return (new StaffResource($staff->fresh(['user'])))->additional([
            'success' => true,
            'message' => 'Staff member updated successfully',
        ])->response();
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/staff/{id}",
     *     operationId="deleteStaff",
     *     tags={"Admin"},
     *     summary="Delete a staff member",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $this->authorize('delete', $staff);

        if ($staff->assignedReferrals()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete staff member with assigned referrals',
            ], 422);
        }

        $userId = $staff->user_id;
        $staff->delete();
        
        // Delete associated user
        User::find($userId)?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{user}/assign-role",
     *     operationId="assignRoleToUser",
     *     tags={"Admin"},
     *     summary="Assign role to user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role_id"},
     *             @OA\Property(property="role_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $role = Role::findOrFail($request->role_id);
        $user->assignRole($role);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
            'data' => $user->load(['roles', 'permissions']),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/users/{user}/assign-permission",
     *     operationId="assignPermissionToUser",
     *     tags={"Admin"},
     *     summary="Assign permission to user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"permission_id"},
     *             @OA\Property(property="permission_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function assignPermission(AssignPermissionRequest $request, User $user): JsonResponse
    {
        $permission = Permission::findOrFail($request->permission_id);
        $user->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permission assigned successfully',
            'data' => $user->load(['roles', 'permissions']),
        ]);
    }
}

