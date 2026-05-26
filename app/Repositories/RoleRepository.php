<?php

namespace App\Repositories;

use App\Interfaces\RoleInterface;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository extends BaseRepository implements RoleInterface
{
    protected function model(): string
    {
        return Role::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return Role::where('guard_name', 'api')->get();
    }

    public function getAllWithPermissions(): Collection
    {
        return Role::where('guard_name', 'api')->with('permissions')->get();
    }

    public function create(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['guard_name'] = 'api';

        return Role::create($data);
    }

    public function dupliquer(int $id): Role
    {
        $role = Role::with('permissions')->findOrFail($id);
        $copy = Role::create([
            'name' => $role->name.' (copie)',
            'guard_name' => 'api',
        ]);
        $copy->syncPermissions($role->permissions);

        return $copy->load('permissions');
    }
}
