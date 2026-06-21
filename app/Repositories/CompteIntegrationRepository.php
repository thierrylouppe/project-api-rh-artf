<?php

namespace App\Repositories;

use App\Interfaces\CompteIntegrationInterface;
use App\Models\CompteIntegration;

class CompteIntegrationRepository extends BaseRepository implements CompteIntegrationInterface
{
    protected function model(): string
    {
        return CompteIntegration::class;
    }

    public function findByAgent(int $agentId): ?CompteIntegration
    {
        return CompteIntegration::where('agent_id', $agentId)->first();
    }

    public function findByLogin(string $login): ?CompteIntegration
    {
        return CompteIntegration::where('login', $login)->first();
    }

    public function marquerMotDePasseEnvoye(int $id): CompteIntegration
    {
        $compte = $this->findById($id);
        $compte->update(['mot_de_passe_provisoire_envoye' => true]);

        return $compte->fresh();
    }
}
