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
        return Inertia::render('AuthSecurity/Permissions/Index', [
            'permissions' => $this->permissionService->getAllPermissions()
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
