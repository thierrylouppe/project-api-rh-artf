<?php

namespace App\Repositories;

use App\Enums\CodeTypeSanction;
use App\Enums\StatutSanction;
use App\Interfaces\SanctionInterface;
use App\Models\Sanction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SanctionRepository extends BaseRepository implements SanctionInterface
{
    /** @var list<string> */
    private const RELATIONS = [
        'agent:id,matricule,nom,prenom',
        'typeSanction',
        'validateur:id,name',
        'createur:id,name',
        'pieces.uploader:id,name',
    ];

    protected function model(): string
    {
        return Sanction::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Sanction::query()->with(self::RELATIONS);

        if (method_exists(Sanction::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Sanction::query()
            ->where('agent_id', $agentId)
            ->with(self::RELATIONS)
            ->orderByDesc('date_faits')
            ->get();
    }

    public function getByStatut(string $statut): Collection
    {
        return Sanction::query()
            ->where('statut', $statut)
            ->with(self::RELATIONS)
            ->orderBy('created_at')
            ->get();
    }

    public function getByCreatedBy(int $userId): Collection
    {
        return Sanction::query()
            ->where('created_by', $userId)
            ->with(self::RELATIONS)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPrononceesDepuis(int $agentId, string $depuis, ?int $exclureId = null): Collection
    {
        return Sanction::query()
            ->where('agent_id', $agentId)
            ->where('statut', StatutSanction::VALIDEE)
            ->where(function ($query) use ($depuis) {
                $query->whereDate('date_decision', '>=', $depuis)
                    ->orWhere(function ($inner) use ($depuis) {
                        $inner->whereNull('date_decision')->whereDate('created_at', '>=', $depuis);
                    });
            })
            ->when($exclureId, fn ($query) => $query->where('id', '!=', $exclureId))
            ->with(['typeSanction'])
            ->orderByDesc('date_decision')
            ->get();
    }

    public function getMisesAPiedEnCours(string $jour): Collection
    {
        return $this->queryMisesAPiedValidees()
            ->whereDate('date_debut_effet', '<=', $jour)
            ->whereDate('date_fin_effet', '>=', $jour)
            ->with('agent')
            ->get();
    }

    public function getMisesAPiedEchues(string $jour): Collection
    {
        return $this->queryMisesAPiedValidees()
            ->whereDate('date_fin_effet', '<', $jour)
            ->with('agent')
            ->get();
    }

    public function agentAUneMiseAPiedEnCours(int $agentId, string $jour): bool
    {
        return $this->queryMisesAPiedValidees()
            ->where('agent_id', $agentId)
            ->whereDate('date_debut_effet', '<=', $jour)
            ->whereDate('date_fin_effet', '>=', $jour)
            ->exists();
    }

    private function queryMisesAPiedValidees(): Builder
    {
        return Sanction::query()
            ->where('statut', StatutSanction::VALIDEE)
            ->whereHas('typeSanction', fn ($query) => $query->where('code', CodeTypeSanction::MISE_A_PIED));
    }

    public function existsByType(int $typeId): bool
    {
        return Sanction::query()->where('type_sanction_id', $typeId)->exists();
    }
}
