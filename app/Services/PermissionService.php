<?php

namespace App\Services;

use App\Interfaces\PermissionInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public function __construct(private PermissionInterface $repository) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function assignToRole(int $roleId, array $permissionNames): Role
    {
        return $this->repository->assignToRole($roleId, $permissionNames);
    }
}
