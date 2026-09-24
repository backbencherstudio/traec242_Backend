<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

#[Group('admin-role', weight: 4)]
class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'status' => true,
            'message' => 'Role list fetched successfully',
            'data' => $roles,
        ], 200);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role created successfully',
            'data' => $role->load('permissions'),
        ], 201);
    }

    public function edit($id): JsonResponse
    {
        $role = Role::with('permissions')->find($id);
        if (! $role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Role fetched successfully',
            'data' => $role,
        ], 200);
    }

    public function update(UpdateRoleRequest $request, $id): JsonResponse
    {
        $role = Role::find($id);
        if (! $role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $role->update([
            'name' => $request->name,
            'guard_name' => 'api',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Role updated successfully',
            'data' => $role,
        ], 200);
    }
}
