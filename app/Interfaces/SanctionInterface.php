<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface SanctionInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getByStatut(string $statut): Collection;

    public function getByCreatedBy(int $userId): Collection;

    public function getPrononceesDepuis(int $agentId, string $depuis, ?int $exclureId = null): Collection;

    public function getMisesAPiedEnCours(string $jour): Collection;

    public function getMisesAPiedEchues(string $jour): Collection;

    public function agentAUneMiseAPiedEnCours(int $agentId, string $jour): bool;

    public function existsByType(int $typeId): bool;
}
