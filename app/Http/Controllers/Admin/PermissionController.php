<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();

        return response()->json([
            'status' => true,
            'message' => 'Permission list fetched successfully',
            'data' => $permissions,
        ], 200);
    }

    public function store(Request $request)
    {

        $role = Role::find($request->role_id);

        if (! $role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $permissions = Permission::whereIn('id', $request->permission_id)->get();

        if ($permissions->count() !== count($request->permission_id)) {
            return response()->json(['message' => 'Some permissions not found'], 404);
        }

        $role->syncPermissions($permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'role' => $role->name,
            'permissions' => $permissions->pluck('name'),
        ]);
    }

    public function edit($id)
    {

        $role = Role::findOrFail($id);

        if (! $role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $currentPermissions = $role->permissions()->pluck('id');
        $allPermissions = Permission::all();

        return response()->json([
            'status' => true,
            'role' => $role->name,
            'current_permission_ids' => $currentPermissions,
            'all_permissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $request->validate([
            'permission_id' => 'required|array',
            'permission_id.*' => 'exists:permissions,id',
        ]);

        $permissions = Permission::whereIn('id', $request->permission_id)->get();

        $role->syncPermissions($permissions);

        return response()->json([
            'message' => 'Permissions updated successfully',
            'role' => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
        ]);
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if (! $role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->syncPermissions([]);

        return response()->json([
            'status' => true,
            'message' => 'All permissions removed from role '.$role->name,
        ]);
    }
}
