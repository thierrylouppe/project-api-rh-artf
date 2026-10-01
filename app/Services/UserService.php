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

        return $this->pourReponse($user->load('roles'));
    }

    public function updateUser(int $id, array $data): User
    {
        if (isset($data['role'])) {
            $user = $this->findById($id);
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        return $this->pourReponse($this->update($id, $data)->load('roles'));
    }

    public function assignRole(int $userId, string $role): User
    {
        $user = $this->findById($userId);
        $user->assignRole($role);

        return $this->pourReponse($user->load('roles'));
    }

    public function revokeRole(int $userId, string $role): User
    {
        $user = $this->findById($userId);
        $user->removeRole($role);

        return $this->pourReponse($user->load('roles'));
    }

    public function activer(int $id): User
    {
        return $this->pourReponse($this->update($id, ['is_active' => true]));
    }

    public function desactiver(int $id): User
    {
        return $this->pourReponse($this->update($id, ['is_active' => false]));
    }

    // ─── Vague F — cloisonnement ──────────────────────────────────────────────

    /**
     * Rattache (ou détache) un utilisateur à un bureau DRHL.
     * Passer bureau_id = null retire le rattachement (accès global).
     */
    public function rattacherBureau(int $userId, ?int $bureauId): User
    {
        return $this->pourReponse(
            $this->update($userId, ['bureau_id' => $bureauId])->load('roles')
        );
    }

    /** @return list<string> */
    public function relationsContexte(): array
    {
        return User::relationsContexte();
    }

    private function pourReponse(User $user): User
    {
        return $user->chargerContexte();
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
