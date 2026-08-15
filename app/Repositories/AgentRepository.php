<?php

namespace App\Repositories;

use App\Enums\StatutDossier;
use App\Interfaces\AgentInterface;
use App\Models\Agent;
use Illuminate\Support\Collection;

class AgentRepository extends BaseRepository implements AgentInterface
{
    protected function model(): string
    {
        return Agent::class;
    }

    public function findByMatricule(string $matricule): ?Agent
    {
        return Agent::where('matricule', $matricule)->first();
    }

    public function getByStatut(string $statut): Collection
    {
        return Agent::where('statut', $statut)->get();
    }

    public function getIntegres(array $filters = []): Collection
    {
        return Agent::query()
            ->where('statut', '!=', 'stagiaire')
            ->whereHas('dossierIntegration', function ($query) {
                $query->where('statut', StatutDossier::INTEGRE->value);
            })
            ->filter($filters)
            ->with($this->relationsListePersonnel())
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    public function getStagiaires(array $filters = []): Collection
    {
        return Agent::query()
            ->where('statut', 'stagiaire')
            ->filter($filters)
            ->with($this->relationsListePersonnel())
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();
    }

    /** @return list<string> */
    private function relationsListePersonnel(): array
    {
        return [
            'grade',
            'categorie',
            'echelon',
            'fonction',
            'typeIntegration',
            'affectationActive',
            'nominationActive',
            'contratActif',
        ];
    }

    public function assignerMatricule(int $agentId, string $matricule): Agent
    {
        $agent = $this->findById($agentId);
        $agent->update(['matricule' => $matricule]);

        return $agent->fresh();
    }

    public function modifierMatricule(int $agentId, string $nouveauMatricule): Agent
    {
        $agent = $this->findById($agentId);
        $agent->update(['matricule' => $nouveauMatricule]);

        return $agent->fresh();
    }

    public function matriculeEstPris(string $matricule, int $excludeAgentId): bool
    {
        return Agent::where('matricule', $matricule)
            ->where('id', '!=', $excludeAgentId)
            ->exists();
    }
}
