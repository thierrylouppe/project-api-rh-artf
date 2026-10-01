<?php

namespace App\Interfaces;

use App\Models\User;

interface UserInterface extends BaseInterface
{
    public function findByEmail(string $email): ?User;

    public function findByAgentId(int $agentId): ?User;

    public function findOptional(int $id): ?User;

    public function getByRole(string $role): \Illuminate\Support\Collection;
}
