<?php

namespace App\Services\AuthSecurity;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    public function createPermission(array $data): Permission
    {
        return Permission::create(['name' => $data['name']]);
    }

    public function updatePermission(Permission $permission, array $data): Permission
    {
        $permission->update(['name' => $data['name']]);
        return $permission;
    }

    public function deletePermission(Permission $permission): bool
    {
        return $permission->delete();
    }
}
