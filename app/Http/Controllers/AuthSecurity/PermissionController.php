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
        $grouped = $this->groupPermissions($permissions);

        return Inertia::render('AuthSecurity/Permissions/Index', [
            'grouped_permissions' => $grouped,
        ]);
    }

    public function create()
    {
        // Pass existing model names as suggestions
        $existing = Permission::all()->map(function ($p) {
            return explode(':', $p->name, 2)[0];
        })->unique()->sort()->values();

        return Inertia::render('AuthSecurity/Permissions/Create', [
            'existing_models' => $existing,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'model'   => 'required|string|regex:/^[a-z0-9\-\.]+$/i',
            'actions' => 'required|array|min:1',
            'actions.*' => 'required|string|regex:/^[a-z0-9\-]+$/i',
        ]);

        $model   = strtolower(trim($request->model));
        $actions = array_map('strtolower', $request->actions);

        $this->permissionService->createForModel($model, $actions);

        return redirect()->route('permissions.index')->with('success', 'Permissions created successfully.');
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('AuthSecurity/Permissions/Edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
        ]);

        $this->permissionService->updatePermission($permission, $validated);

        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $this->permissionService->deletePermission($permission);
        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully.');
    }

    private function groupPermissions($permissions): array
    {
        $grouped = [];
        foreach ($permissions as $permission) {
            $parts  = explode(':', $permission->name, 2);
            $group  = $parts[0];
            $label  = ucwords(str_replace(['-', '.'], ' ', $group));
            $grouped[$label][] = [
                'id'     => $permission->id,
                'name'   => $permission->name,
                'action' => $parts[1] ?? $permission->name,
            ];
        }
        ksort($grouped);
        return $grouped;
    }
}
