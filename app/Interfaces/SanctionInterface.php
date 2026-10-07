<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface SanctionInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getByStatut(string $statut): Collection;

    public function getByCreatedBy(int $userId): Collection;

    public function getPrononceesDepuis(int $agentId, string $depuis, ?int $exclureId = null): Collection;

    /** Sanctions prononcées dont la décision (ou la création) tombe dans [debut, fin]. */
    public function getPrononceesEntre(int $agentId, string $debut, string $fin): Collection;

    /** Sanctions validées depuis une date, groupées par agent_id. */
    public function getPrononceesDepuisTous(string $depuis): Collection;

    /** Mises à pied validées chevauchant [debut, fin], groupées par agent_id. */
    public function getMisesAPiedCouvrant(string $debut, string $fin): Collection;

    public function getMisesAPiedEnCours(string $jour): Collection;

    public function getMisesAPiedEchues(string $jour): Collection;

    public function agentAUneMiseAPiedEnCours(int $agentId, string $jour): bool;

    public function existsByType(int $typeId): bool;
}
