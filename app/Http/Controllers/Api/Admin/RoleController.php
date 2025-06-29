<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Str;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->orderBy('name')->get();

        $permissions = Permission::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        DB::beginTransaction();

        try {
            $data['slug'] = Str::slug($data['name']);

            $permissionIds = $data['permission_ids'] ?? [];

            unset($data['permission_ids']);

            $role = Role::create($data);

            if (!empty($permissionIds)) {
                $role->permissions()->sync($permissionIds);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Role created and permissions assigned successfully.',
                'data' => $role->load('permissions')
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $role
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        DB::beginTransaction();

        try {
            $data['slug'] = Str::slug($data['name']);

            $permissionIds = $data['permission_ids'] ?? [];
            unset($data['permission_ids']);

            $role->update($data);

            $role->permissions()->sync($permissionIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Роль успешно обновлена.',
                'data' => $role->load('permissions'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении роли: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
