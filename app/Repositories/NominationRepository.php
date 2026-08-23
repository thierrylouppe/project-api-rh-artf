<?php

namespace App\Repositories;

use App\Enums\StatutNomination;
use App\Interfaces\NominationInterface;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Nomination;
use App\Models\Service;
use Illuminate\Support\Collection;

class NominationRepository extends BaseRepository implements NominationInterface
{
    protected function model(): string
    {
        return Nomination::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Nomination::query()
            ->where('agent_id', $agentId)
            ->with('structure')
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();
    }

    public function getActive(int $agentId): ?Nomination
    {
        return Nomination::query()
            ->where('agent_id', $agentId)
            ->where('statut', StatutNomination::ACTIVE)
            ->latest('id')
            ->first();
    }

    public function cloturer(int $id, ?string $dateFin): Nomination
    {
        $nomination = $this->findById($id);
        $nomination->update([
            'statut'   => StatutNomination::CLOTUREE,
            'date_fin' => $dateFin ?? now()->toDateString(),
        ]);

        return $nomination->fresh();
    }

    public function cloturerActivesPourStructure(string $structurableType, int $structurableId, ?int $saufId = null): void
    {
        Nomination::query()
            ->where('structurable_type', $structurableType)
            ->where('structurable_id', $structurableId)
            ->where('statut', StatutNomination::ACTIVE)
            ->when($saufId !== null, fn ($q) => $q->where('id', '!=', $saufId))
            ->update([
                'statut'   => StatutNomination::CLOTUREE,
                'date_fin' => now()->toDateString(),
            ]);
    }

    public function cloturerActivePourAgent(int $agentId, ?int $saufId = null): void
    {
        Nomination::query()
            ->where('agent_id', $agentId)
            ->where('statut', StatutNomination::ACTIVE)
            ->when($saufId !== null, fn ($q) => $q->where('id', '!=', $saufId))
            ->update([
                'statut'   => StatutNomination::CLOTUREE,
                'date_fin' => now()->toDateString(),
            ]);
    }

    public function getHistoriqueByAgent(int $agentId): Collection
    {
        return Nomination::query()
            ->where('agent_id', $agentId)
            ->where('statut', '!=', StatutNomination::ACTIVE)
            ->with('structure')
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();
    }

    public function postesVacants(): Collection
    {
        $occupees = Nomination::query()
            ->where('statut', StatutNomination::ACTIVE)
            ->get(['structurable_type', 'structurable_id'])
            ->map(fn (Nomination $n) => $n->structurable_type.'#'.$n->structurable_id)
            ->all();

        $postes = [
            Direction::class => ['Directeur Général', 'Directeur Central', 'Directeur Départemental'],
            Service::class   => ['Chef de Service'],
            Bureau::class    => ['Chef de Bureau'],
        ];

        $result = collect();

        foreach ([
            Direction::class => Direction::query()->orderBy('nom')->get(),
            Service::class   => Service::query()->orderBy('nom')->get(),
            Bureau::class    => Bureau::query()->orderBy('nom')->get(),
        ] as $type => $structures) {
            foreach ($structures as $structure) {
                if (in_array($type.'#'.$structure->id, $occupees, true)) {
                    continue;
                }

                $result->push([
                    'structurable_type' => $type,
                    'structurable_id'   => $structure->id,
                    'nom'               => $structure->nom,
                    'type'              => class_basename($type),
                    'postes_possibles'  => $postes[$type],
                ]);
            }
        }

        return $result;
    }

    public function getByLot(int $lotId): Collection
    {
        return Nomination::query()
            ->where('lot_nomination_id', $lotId)
            ->orderBy('id')
            ->get();
    }

    public function updateStatutByLot(int $lotId, StatutNomination $statut): void
    {
        Nomination::query()
            ->where('lot_nomination_id', $lotId)
            ->update(['statut' => $statut]);
    }
}
