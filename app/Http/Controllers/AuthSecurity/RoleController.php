<?php

namespace App\Http\Controllers\AuthSecurity;

use App\Http\Controllers\Controller;
use App\Services\AuthSecurity\RoleService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService)
    {
    }

    public function index()
    {
        return Inertia::render('AuthSecurity/Roles/Index', [
            'roles' => $this->roleService->getAllRoles(),
            'permissions' => Permission::all()
        ]);
    }

    public function create()
    {
        return Inertia::render('AuthSecurity/Roles/Create', [
            'permissions' => Permission::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $this->roleService->createRole($validated);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        return Inertia::render('AuthSecurity/Roles/Edit', [
            'role' => $role,
            'permissions' => Permission::all()
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $this->roleService->updateRole($role, $validated);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->roleService->deleteRole($role);
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    // --- Internal API Endpoints ---

    public function apiIndex()
    {
        return response()->json($this->roleService->getAllRoles());
    }

    public function apiShow(Role $role)
    {
        $role->load('permissions');
        return response()->json($role);
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = $this->roleService->createRole($validated);
        return response()->json(['message' => 'Role created', 'role' => $role], 201);
    }

    public function apiUpdate(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role = $this->roleService->updateRole($role, $validated);
        return response()->json(['message' => 'Role updated', 'role' => $role]);
    }

    public function apiDestroy(Role $role)
    {
        $this->roleService->deleteRole($role);
        return response()->json(['message' => 'Role deleted'], 204);
    }
}
