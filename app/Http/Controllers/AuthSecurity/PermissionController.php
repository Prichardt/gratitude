<?php

namespace App\Http\Controllers\AuthSecurity;

use App\Http\Controllers\Controller;
use App\Services\AuthSecurity\PermissionService;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService)
    {
    }

    public function index()
    {
        $permissions = $this->permissionService->getAllPermissions();
        $grouped = [];
        foreach ($permissions as $permission) {
            $parts = explode(':', $permission->name, 2);
            $group = ucwords(str_replace(['-', '.'], ' ', $parts[0]));
            $grouped[$group][] = ['id' => $permission->id, 'name' => $permission->name, 'action' => $parts[1] ?? $permission->name];
        }
        ksort($grouped);

        return Inertia::render('AuthSecurity/Permissions/Index', [
            'grouped_permissions' => $grouped,
            'permissions' => $permissions,
        ]);
    }

    public function create()
    {
        return Inertia::render('AuthSecurity/Permissions/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name'
        ]);

        $this->permissionService->createPermission($validated);

        return redirect()->route('permissions.index')->with('success', 'Permission created successfully.');
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('AuthSecurity/Permissions/Edit', [
            'permission' => $permission
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id
        ]);

        $this->permissionService->updatePermission($permission, $validated);

        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $this->permissionService->deletePermission($permission);
        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
    }
}
