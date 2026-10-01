<?php

namespace App\Repositories;

use App\Interfaces\UserInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository implements UserInterface
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByAgentId(int $agentId): ?User
    {
        return User::query()->where('agent_id', $agentId)->first();
    }

    public function findOptional(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function getByRole(string $role): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->role($role, 'api')
            ->get();
    }
}
