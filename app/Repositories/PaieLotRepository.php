<?php

namespace App\Repositories;

use App\Enums\StatutPaieLot;
use App\Interfaces\PaieLotInterface;
use App\Models\PaieLot;
use App\Models\PaieLotLigne;
use App\Models\PaieLotLigneDetail;
use Illuminate\Support\Collection;

class PaieLotRepository extends BaseRepository implements PaieLotInterface
{
    protected function model(): string
    {
        return PaieLot::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PaieLot::query();

        if (method_exists(PaieLot::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('annee')->orderByDesc('mois')->get();
    }

    public function findByPeriode(int $annee, int $mois): ?PaieLot
    {
        return PaieLot::query()->where('annee', $annee)->where('mois', $mois)->first();
    }

    public function getLignes(int $lotId): Collection
    {
        return PaieLotLigne::query()
            ->where('lot_id', $lotId)
            ->with(['agent:id,matricule,nom,prenom,statut', 'details', 'salaireAgent.salaire'])
            ->orderBy('id')
            ->get();
    }

    public function getLigne(int $lotId, int $ligneId): PaieLotLigne
    {
        return PaieLotLigne::query()
            ->where('lot_id', $lotId)
            ->with(['agent:id,matricule,nom,prenom,statut,fonction_id', 'agent.fonction:id,nom,sigle', 'details', 'lot'])
            ->findOrFail($ligneId);
    }

    public function getLignesClotureesParAgent(int $agentId): Collection
    {
        return PaieLotLigne::query()
            ->where('agent_id', $agentId)
            ->whereHas('lot', fn ($q) => $q->where('statut', StatutPaieLot::CLOTURE))
            ->with(['lot', 'details'])
            ->orderByDesc('id')
            ->get();
    }

    public function supprimerLignes(int $lotId): void
    {
        PaieLotLigne::query()->where('lot_id', $lotId)->delete();
    }

    public function creerLigne(array $data): PaieLotLigne
    {
        return PaieLotLigne::query()->create($data);
    }

    public function creerDetails(int $ligneId, array $details): void
    {
        foreach ($details as $detail) {
            $detail['ligne_id'] = $ligneId;
            PaieLotLigneDetail::query()->create($detail);
        }
    }

    public function updateLigne(int $ligneId, array $data): void
    {
        PaieLotLigne::query()->where('id', $ligneId)->update($data);
    }

    public function existeSnapshotVerrouille(int $agentId, int $elementId): bool
    {
        return PaieLotLigneDetail::query()
            ->where('paie_element_id', $elementId)
            ->whereHas('ligne', function ($q) use ($agentId) {
                $q->where('agent_id', $agentId)
                    ->whereHas('lot', fn ($lot) => $lot->whereIn('statut', [
                        StatutPaieLot::VALIDE->value,
                        StatutPaieLot::CLOTURE->value,
                    ]));
            })
            ->exists();
    }
}
