<?php

namespace App\Repositories;

use App\Enums\StatutAffiliation;
use App\Enums\TypeOrganismeSocial;
use App\Interfaces\AffiliationSocialeInterface;
use App\Models\AffiliationSociale;
use App\Models\Agent;
use App\Models\OrganismeSocial;
use Illuminate\Support\Collection;

class AffiliationSocialeRepository extends BaseRepository implements AffiliationSocialeInterface
{
    protected function model(): string
    {
        return AffiliationSociale::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = AffiliationSociale::query()
            ->with([
                'agent:id,matricule,nom,prenom,numero_cnss,statut',
                'organisme',
            ]);

        if (method_exists(AffiliationSociale::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('date_debut')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return AffiliationSociale::query()
            ->where('agent_id', $agentId)
            ->with(['organisme', 'agent:id,matricule,nom,prenom,numero_cnss,statut'])
            ->orderByDesc('date_debut')
            ->get();
    }

    public function findActiveForAgentAndOrganisme(int $agentId, int $organismeId, ?int $excludeId = null): ?AffiliationSociale
    {
        return AffiliationSociale::query()
            ->where('agent_id', $agentId)
            ->where('organisme_id', $organismeId)
            ->where('statut', StatutAffiliation::ACTIVE)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    public function agentsSansAffiliationCnss(): Collection
    {
        $cnssIds = OrganismeSocial::query()
            ->where('type', TypeOrganismeSocial::CNSS)
            ->pluck('id');

        return Agent::query()
            ->whereNotIn('statut', ['archive', 'stagiaire'])
            ->whereDoesntHave('affiliations', function ($query) use ($cnssIds) {
                $query->whereIn('organisme_id', $cnssIds)
                    ->where('statut', StatutAffiliation::ACTIVE);
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'matricule', 'nom', 'prenom', 'numero_cnss', 'statut']);
    }
}
