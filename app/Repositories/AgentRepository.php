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

    public function getAll(array $filters = []): Collection
    {
        $query = Agent::query()->with(['fonction', 'nominationActive']);

        if (method_exists(Agent::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
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
        $query = Agent::query()
            ->whereHas('dossierIntegration', function ($query) {
                $query->where('statut', StatutDossier::INTEGRE->value);
            });

        if (($filters['statut'] ?? '') === 'archive') {
            $query->where('statut', 'archive');
        } else {
            $query->whereNotIn('statut', ['stagiaire', 'archive'])->filter($filters);
        }

        return $query
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

    public function findAvecGrade(int $id): Agent
    {
        return Agent::query()->with('grade')->findOrFail($id);
    }

    public function trouverArchivesPrioritaires(string $nom, string $prenom, ?string $numeroCnss): Collection
    {
        $limite = now()->subYear()->toDateString();
        $nom    = mb_strtolower(trim($nom));
        $prenom = mb_strtolower(trim($prenom));

        return Agent::query()
            ->select(['id', 'matricule', 'nom', 'prenom', 'numero_cnss', 'prioritaire_reembauche_jusquau'])
            ->where('statut', 'archive')
            ->whereNotNull('prioritaire_reembauche_jusquau')
            ->whereDate('prioritaire_reembauche_jusquau', '>=', $limite)
            ->where(function ($q) use ($nom, $prenom, $numeroCnss) {
                $q->where(function ($homonyme) use ($nom, $prenom) {
                    $homonyme
                        ->whereRaw('LOWER(nom) = ?', [$nom])
                        ->whereRaw('LOWER(prenom) = ?', [$prenom]);
                });

                if ($numeroCnss !== null && $numeroCnss !== '') {
                    $q->orWhere('numero_cnss', $numeroCnss);
                }
            })
            ->orderByDesc('prioritaire_reembauche_jusquau')
            ->get();
    }
}
