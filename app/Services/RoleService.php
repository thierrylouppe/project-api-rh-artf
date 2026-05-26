<?php

namespace App\Services;

use App\Interfaces\RoleInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleService extends BaseService
{
    public function __construct(RoleInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getAllWithPermissions(): Collection
    {
        return $this->repository->getAllWithPermissions();
    }

    public function createRole(array $data): Role
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $role = $this->create($data);

        if ($permissions) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function updateRole(int $id, array $data): Role
    {
        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);

        $role = $this->update($id, $data);

        if (is_array($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    public function dupliquer(int $id): Role
    {
        return $this->repository->dupliquer($id);
    }
}
