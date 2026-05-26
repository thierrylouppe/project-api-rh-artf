<?php

namespace App\Interfaces;

use Spatie\Permission\Models\Role;

interface PermissionInterface
{
    public function getAll(): \Illuminate\Support\Collection;

    public function assignToRole(int $roleId, array $permissionNames): Role;
}
