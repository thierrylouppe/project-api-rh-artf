<?php

namespace App\Repositories;

use App\Interfaces\PaieElementAffectationInterface;
use App\Models\PaieElementAffectation;
use Illuminate\Support\Collection;

class PaieElementAffectationRepository extends BaseRepository implements PaieElementAffectationInterface
{
    protected function model(): string
    {
        return PaieElementAffectation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        if (isset($filters['element_id'])) {
            $filters['paie_element_id'] = $filters['element_id'];
            unset($filters['element_id']);
        }

        $actives = $filters['actives'] ?? null;
        unset($filters['actives']);

        $query = PaieElementAffectation::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut,fonction_id',
                'agent.fonction:id,nom,sigle',
                'element',
            ]);

        if (method_exists(PaieElementAffectation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        if ($actives === '1' || $actives === 1 || $actives === true || $actives === 'true') {
            $au = now()->toDateString();
            $query->where('date_debut', '<=', $au)
                ->where(function ($q) use ($au) {
                    $q->whereNull('date_fin')->orWhere('date_fin', '>=', $au);
                });
        }

        return $query->orderByDesc('date_debut')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->getAll(['agent_id' => $agentId]);
    }

    public function findChevauchement(
        int $agentId,
        int $elementId,
        string $dateDebut,
        ?string $dateFin,
        ?int $excludeId = null,
    ): ?PaieElementAffectation {
        $fin = $dateFin ?? '9999-12-31';

        return PaieElementAffectation::query()
            ->where('agent_id', $agentId)
            ->where('paie_element_id', $elementId)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('date_debut', '<=', $fin)
            ->where(function ($q) use ($dateDebut) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $dateDebut);
            })
            ->first();
    }

    public function existsByElement(int $elementId): bool
    {
        return PaieElementAffectation::query()->where('paie_element_id', $elementId)->exists();
    }

    public function getCouvrantPeriode(int $agentId, string $debut, string $fin): Collection
    {
        return PaieElementAffectation::query()
            ->with('element')
            ->where('agent_id', $agentId)
            ->where('date_debut', '<=', $fin)
            ->where(function ($q) use ($debut) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $debut);
            })
            ->orderBy('date_debut')
            ->get();
    }
}
