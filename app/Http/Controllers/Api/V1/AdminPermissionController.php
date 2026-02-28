<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

/**
 * @OA\Tag(
 *     name="Admin",
 *     description="Admin API endpoints for managing permissions"
 * )
 */
class AdminPermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     operationId="listPermissions",
     *     tags={"Admin"},
     *     summary="List all permissions",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if (! Gate::allows('viewAny', Permission::class)) {
            abort(403, 'Unauthorized action.');
        }

        $query = Permission::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $permissions = $query->withCount('roles')
            ->orderBy('name')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions/{id}",
     *     operationId="getPermission",
     *     tags={"Admin"},
     *     summary="View permission details",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        $permission->load('roles');

        return response()->json([
            'success' => true,
            'data' => $permission,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/admin/permissions",
     *     operationId="createPermission",
     *     tags={"Admin"},
     *     summary="Create a new permission",
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission created successfully',
            'data' => $permission,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/permissions/{id}",
     *     operationId="updatePermission",
     *     tags={"Admin"},
     *     summary="Update a permission",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $this->authorize('update', $permission);

        $permission->update(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/permissions/{id}",
     *     operationId="deletePermission",
     *     tags={"Admin"},
     *     summary="Delete a permission",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        if ($permission->roles()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete permission assigned to roles',
            ], 422);
        }

        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }
}
