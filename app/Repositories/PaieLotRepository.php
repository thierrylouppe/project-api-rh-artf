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

    public function getLignes(int $lotId, array $filters = []): Collection
    {
        $query = PaieLotLigne::query()
            ->where('lot_id', $lotId)
            ->with(['agent:id,matricule,nom,prenom,statut', 'details', 'salaireAgent.salaire']);

        if (! empty($filters['agent_id'])) {
            $query->where('agent_id', (int) $filters['agent_id']);
        }

        if (array_key_exists('hors_grille', $filters) && $filters['hors_grille'] !== '' && $filters['hors_grille'] !== null) {
            $horsGrille = filter_var($filters['hors_grille'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($horsGrille !== null) {
                $query->where('hors_grille', $horsGrille);
            }
        }

        if (! empty($filters['q'])) {
            $terme = (string) $filters['q'];
            $query->whereHas('agent', function ($agent) use ($terme) {
                $agent->where(function ($w) use ($terme) {
                    $w->where('nom', 'like', "%{$terme}%")
                        ->orWhere('prenom', 'like', "%{$terme}%")
                        ->orWhere('matricule', 'like', "%{$terme}%");
                });
            });
        }

        return $query->orderBy('id')->get();
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
        if ($details === []) {
            return;
        }

        $now = now();
        $rows = array_map(function (array $detail) use ($ligneId, $now) {
            $detail['ligne_id'] = $ligneId;
            $detail['created_at'] = $now;
            $detail['updated_at'] = $now;
            if (array_key_exists('meta', $detail)) {
                $detail['meta'] = $detail['meta'] === null ? null : json_encode($detail['meta']);
            }

            return $detail;
        }, $details);

        PaieLotLigneDetail::query()->insert($rows);
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

    public function existeSnapshotElementVerrouille(int $elementId): bool
    {
        return PaieLotLigneDetail::query()
            ->where('paie_element_id', $elementId)
            ->whereHas('ligne.lot', fn ($lot) => $lot->whereIn('statut', [
                StatutPaieLot::VALIDE->value,
                StatutPaieLot::CLOTURE->value,
            ]))
            ->exists();
    }
}
