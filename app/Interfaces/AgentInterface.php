<?php

namespace App\Interfaces;

use App\Models\Agent;
use Illuminate\Support\Collection;

interface AgentInterface extends BaseInterface
{
    public function findByMatricule(string $matricule): ?Agent;

    public function getByStatut(string $statut): Collection;

    /** Agents dont le dossier est INTEGRE, hors stagiaires. */
    public function getIntegres(array $filters = []): Collection;

    /** Agents au statut stagiaire. */
    public function getStagiaires(array $filters = []): Collection;

    public function assignerMatricule(int $agentId, string $matricule): Agent;

    public function modifierMatricule(int $agentId, string $nouveauMatricule): Agent;

    /** Vérifie si un matricule est déjà utilisé par un autre agent. */
    public function matriculeEstPris(string $matricule, int $excludeAgentId): bool;

    public function findAvecGrade(int $id): Agent;

    /**
     * Archives encore dans la fenêtre art. 48 (2 ans + 1 an sous nouvel essai).
     *
     * @return Collection<int, Agent>
     */
    public function trouverArchivesPrioritaires(string $nom, string $prenom, ?string $numeroCnss): Collection;
}
