<?php

namespace App\Interfaces;

use App\Models\CompteIntegration;

interface CompteIntegrationInterface extends BaseInterface
{
    public function findByAgent(int $agentId): ?CompteIntegration;

    public function findByLogin(string $login): ?CompteIntegration;

    public function marquerMotDePasseEnvoye(int $id): CompteIntegration;
}
