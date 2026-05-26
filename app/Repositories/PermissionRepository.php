<?php

namespace App\Repositories;

use App\Interfaces\PermissionInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionRepository implements PermissionInterface
{
    public function getAll(): Collection
    {
        return Permission::where('guard_name', 'api')->orderBy('name')->get();
    }

    public function assignToRole(int $roleId, array $permissionNames): Role
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissionNames);

        return $role->load('permissions');
    }
}
