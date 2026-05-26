<?php

namespace App\Services;

use App\Interfaces\UserInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserService extends BaseService
{
    public function __construct(UserInterface $repository)
    {
        parent::__construct($repository);
    }

    public function createUser(array $data): User
    {
        $user = $this->create($data);

        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user->load('roles');
    }

    public function updateUser(int $id, array $data): User
    {
        if (isset($data['role'])) {
            $user = $this->findById($id);
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        return $this->update($id, $data)->load('roles');
    }

    public function assignRole(int $userId, string $role): User
    {
        $user = $this->findById($userId);
        $user->assignRole($role);

        return $user->load('roles');
    }

    public function revokeRole(int $userId, string $role): User
    {
        $user = $this->findById($userId);
        $user->removeRole($role);

        return $user->load('roles');
    }

    public function activer(int $id): User
    {
        return $this->update($id, ['is_active' => true]);
    }

    public function desactiver(int $id): User
    {
        return $this->update($id, ['is_active' => false]);
    }

    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        return $data;
    }

    protected function afterCreate(Model $model): Model
    {
        return $model;
    }
}
