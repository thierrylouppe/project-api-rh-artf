<?php

namespace App\Repositories;

use App\Enums\StatutAffectation;
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

    public function getByAgent(int $agentId): Collection
    {
        return Affectation::where('agent_id', $agentId)->get();
    }

    public function getActive(int $agentId): ?Affectation
    {
        return Affectation::where('agent_id', $agentId)
            ->where('statut', StatutAffectation::ACTIVE)
            ->latest()
            ->first();
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

    public function resoudreSuperiorParStructure(string $structurableType, int $structurableId): ?int
    {
        $nomination = $this->nominationActiveParStructure($structurableType, $structurableId);
        if ($nomination) {
            return $nomination->agent_id;
        }

        if ($structurableType === Bureau::class) {
            $bureau = Bureau::find($structurableId);
            if ($bureau?->service_id) {
                $nomination = $this->nominationActiveParStructure(Service::class, $bureau->service_id);
                if ($nomination) {
                    return $nomination->agent_id;
                }

                $service = Service::find($bureau->service_id);
                if ($service?->direction_id) {
                    $nomination = $this->nominationActiveParStructure(Direction::class, $service->direction_id);
                    return $nomination?->agent_id;
                }
            }
        }

        if ($structurableType === Service::class) {
            $service = Service::find($structurableId);
            if ($service?->direction_id) {
                $nomination = $this->nominationActiveParStructure(Direction::class, $service->direction_id);
                return $nomination?->agent_id;
            }
        }

        return null;
    }

    private function nominationActiveParStructure(string $type, int $id): ?Nomination
    {
        return Nomination::where('structurable_type', $type)
            ->where('structurable_id', $id)
            ->where('statut', 'active')
            ->latest()
            ->first();
    }
}
