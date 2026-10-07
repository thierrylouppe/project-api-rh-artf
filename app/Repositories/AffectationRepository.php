<?php

namespace App\Repositories;

use App\Enums\StatutAffectation;
use App\Enums\StatutNomination;
use App\Interfaces\AffectationInterface;
use App\Models\Affectation;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Nomination;
use App\Models\Service;
use Illuminate\Support\Collection;

class AffectationRepository extends BaseRepository implements AffectationInterface
{
    protected function model(): string
    {
        return Affectation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Affectation::query()->with('agent');

        if (method_exists(Affectation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Affectation::where('agent_id', $agentId)->with('agent')->get();
    }

    public function getActive(int $agentId): ?Affectation
    {
        return Affectation::where('agent_id', $agentId)
            ->where('statut', StatutAffectation::ACTIVE)
            ->latest()
            ->first();
    }

    public function getActivesParSuperieur(int $superieurId): Collection
    {
        return Affectation::query()
            ->where('superieur_hierarchique_id', $superieurId)
            ->where('statut', StatutAffectation::ACTIVE)
            ->with(['agent', 'structure'])
            ->orderByDesc('date_affectation')
            ->get();
    }

    public function getPourAgentSurPeriode(int $agentId, $debut, $fin): Collection
    {
        return Affectation::query()
            ->where('agent_id', $agentId)
            ->whereIn('statut', [StatutAffectation::ACTIVE, StatutAffectation::TERMINEE])
            ->whereDate('date_affectation', '<', $fin)
            ->where(function ($query) use ($debut) {
                $query->whereNull('date_fin')
                    ->orWhereDate('date_fin', '>=', $debut);
            })
            ->with('structure')
            ->orderBy('date_affectation')
            ->get();
    }

    public function terminer(int $id, ?string $dateFin): Affectation
    {
        $affectation = $this->findById($id);
        $affectation->update([
            'statut'   => StatutAffectation::TERMINEE,
            'date_fin' => $dateFin ?? now()->toDateString(),
        ]);

        return $affectation->fresh();
    }

    public function resoudreSuperiorParStructure(string $structurableType, int $structurableId, ?int $saufAgentId = null): ?int
    {
        $nomination = $this->nominationActiveParStructure($structurableType, $structurableId, $saufAgentId);
        if ($nomination) {
            return (int) $nomination->agent_id;
        }

        if ($structurableType === Bureau::class) {
            $bureau = Bureau::find($structurableId);
            if ($bureau?->service_id) {
                $nomination = $this->nominationActiveParStructure(Service::class, $bureau->service_id, $saufAgentId);
                if ($nomination) {
                    return (int) $nomination->agent_id;
                }

                $service = Service::find($bureau->service_id);
                if ($service?->direction_id) {
                    $nomination = $this->nominationActiveParStructure(Direction::class, $service->direction_id, $saufAgentId);

                    return $nomination ? (int) $nomination->agent_id : null;
                }
            }
        }

        if ($structurableType === Service::class) {
            $service = Service::find($structurableId);
            if ($service?->direction_id) {
                $nomination = $this->nominationActiveParStructure(Direction::class, $service->direction_id, $saufAgentId);

                return $nomination ? (int) $nomination->agent_id : null;
            }
        }

        return null;
    }

    public function getActivesPourHierarchie(): Collection
    {
        $affectations = Affectation::query()
            ->where('statut', StatutAffectation::ACTIVE)
            ->with('agent.fonction')
            ->orderBy('id')
            ->get();

        $bureaux = Bureau::query()->with('service')->get()->keyBy('id');
        $services = Service::query()->with('direction')->get()->keyBy('id');
        $directions = Direction::query()->get()->keyBy('id');

        return $affectations->each(function (Affectation $affectation) use ($bureaux, $services, $directions) {
            $structure = match ($affectation->structurable_type) {
                Bureau::class => $bureaux->get($affectation->structurable_id),
                Service::class => $services->get($affectation->structurable_id),
                Direction::class => $directions->get($affectation->structurable_id),
                default => null,
            };
            $affectation->setRelation('structure', $structure);
        });
    }

    public function getByLot(int $lotId): Collection
    {
        return Affectation::query()
            ->where('lot_affectation_id', $lotId)
            ->orderBy('id')
            ->get();
    }

    public function updateStatutByLot(int $lotId, StatutAffectation $statut): void
    {
        Affectation::query()
            ->where('lot_affectation_id', $lotId)
            ->update(['statut' => $statut]);
    }

    private function nominationActiveParStructure(string $type, int $id, ?int $saufAgentId = null): ?Nomination
    {
        return Nomination::where('structurable_type', $type)
            ->where('structurable_id', $id)
            ->where('statut', StatutNomination::ACTIVE)
            ->when($saufAgentId !== null, fn ($query) => $query->where('agent_id', '!=', $saufAgentId))
            ->latest()
            ->first();
    }
}
