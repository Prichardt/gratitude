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

    /**
     * Create multiple permissions for a model at once.
     * e.g. model = "product", actions = ["view","create"] → product:view, product:create
     * Returns only the newly created ones (skips duplicates).
     */
    public function createForModel(string $model, array $actions): array
    {
        $created = [];
        foreach ($actions as $action) {
            $name = $model . ':' . $action;
            $permission = Permission::firstOrCreate(['name' => $name]);
            $created[] = $permission;
        }
        return $created;
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
