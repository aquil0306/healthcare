<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing roles"
 * )
 */
class AdminRoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     operationId="listRoles",
     *     tags={"Admin"},
     *     summary="List all roles",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Authorization is handled by middleware (EnsureRole:admin)
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles/{id}",
     *     operationId="getRole",
     *     tags={"Admin"},
     *     summary="View role details",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(Role $role): JsonResponse
    {
        // Authorization is handled by middleware (EnsureRole:admin)
        $role->load(['permissions', 'users']);

        return response()->json([
            'success' => true,
            'data' => $role,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/roles",
     *     operationId="createRole",
     *     tags={"Admin"},
     *     summary="Create a new role",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        // Authorization is handled by middleware (EnsureRole:admin)
        $validated = $request->validated();

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/roles/{id}",
     *     operationId="updateRole",
     *     tags={"Admin"},
     *     summary="Update a role",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        // Authorization is handled by middleware (EnsureRole:admin)
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role->fresh(['permissions']),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/roles/{id}",
     *     operationId="deleteRole",
     *     tags={"Admin"},
     *     summary="Delete a role",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(Role $role): JsonResponse
    {
        // Authorization is handled by middleware (EnsureRole:admin)
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }
}
