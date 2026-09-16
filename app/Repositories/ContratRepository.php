<?php

namespace App\Repositories;

use App\Enums\StatutEssai;
use App\Interfaces\ContratInterface;
use App\Models\Contrat;
use Illuminate\Support\Collection;

class ContratRepository extends BaseRepository implements ContratInterface
{
    protected function model(): string
    {
        return Contrat::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Contrat::where('agent_id', $agentId)
            ->with(['typeContrat', 'fonction'])
            ->get();
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Contrat::query()->with(['typeContrat', 'fonction', 'agent']);

        if (method_exists(Contrat::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function getActif(int $agentId): ?Contrat
    {
        return Contrat::where('agent_id', $agentId)
            ->where('statut', 'actif')
            ->latest()
            ->first();
    }

    public function resilier(int $id): Contrat
    {
        $contrat = $this->findById($id);
        $contrat->update(['statut' => 'resilie']);

        return $contrat->fresh();
    }

    public function getEssaisEcheantLe(string $date): Collection
    {
        return Contrat::query()
            ->whereIn('statut_essai', [StatutEssai::EN_COURS->value, StatutEssai::RENOUVELE->value])
            ->whereDate('date_fin_essai', $date)
            ->with(['agent', 'typeContrat'])
            ->get();
    }
}
