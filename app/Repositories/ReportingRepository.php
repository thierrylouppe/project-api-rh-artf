<?php

namespace App\Repositories;

use App\Enums\StatutAffectation;
use App\Enums\StatutAgent;
use App\Enums\StatutPaieLot;
use App\Enums\StatutSessionEvaluation;
use App\Interfaces\ReportingInterface;
use App\Models\Absence;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Contrat;
use App\Models\DemandeConge;
use App\Models\Direction;
use App\Models\Evaluation;
use App\Models\PaieLot;
use App\Models\Service;
use App\Models\SessionEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class ReportingRepository implements ReportingInterface
{
    private const RELATIONS_AGENT = [
        'grade:id,nom,sigle',
        'fonction:id,nom,sigle',
        'typeIntegration:id,nom',
        'affectationActive',
    ];

    public function agents(array $filters = [], bool $presentOnly = false): Collection
    {
        $agents = $this->queryAgents($filters, $presentOnly)
            ->with(self::RELATIONS_AGENT)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get();

        $this->chargerStructures($agents);

        return $agents;
    }

    public function paginerAgents(array $filters = [], bool $presentOnly = true): LengthAwarePaginator
    {
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        $paginator = $this->queryAgents($filters, $presentOnly)
            ->with(self::RELATIONS_AGENT)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->paginate($perPage);

        $this->chargerStructures($paginator->getCollection());

        return $paginator;
    }

    public function demandesConge(int $annee): Collection
    {
        return DemandeConge::query()
            ->with(['agent:id,matricule,nom,prenom,statut', 'typeConge:id,nom,necessite_n1,necessite_rh,necessite_dg'])
            ->whereYear('date_debut', $annee)
            ->orderBy('date_debut')
            ->get();
    }

    public function absences(int $annee): Collection
    {
        return Absence::query()
            ->with(['agent:id,matricule,nom,prenom,statut', 'typeAbsence:id,nom'])
            ->whereYear('date_debut', $annee)
            ->orderBy('date_debut')
            ->get();
    }

    public function evaluations(int $annee): Collection
    {
        return Evaluation::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'session:id,debut_session,fin_session,statut',
            ])
            ->whereHas('session', fn (Builder $q) => $q->whereYear('debut_session', $annee))
            ->orderBy('id')
            ->get();
    }

    public function sessions(int $annee): Collection
    {
        return SessionEvaluation::query()
            ->whereYear('debut_session', $annee)
            ->orderByDesc('debut_session')
            ->get();
    }

    public function sessionCourante(?int $sessionId = null): ?SessionEvaluation
    {
        if ($sessionId !== null) {
            return SessionEvaluation::query()->find($sessionId);
        }

        $ouverte = SessionEvaluation::query()
            ->where('statut', StatutSessionEvaluation::OUVERTE)
            ->orderByDesc('debut_session')
            ->first();

        if ($ouverte instanceof SessionEvaluation) {
            return $ouverte;
        }

        return SessionEvaluation::query()
            ->where('statut', StatutSessionEvaluation::CLOTUREE)
            ->orderByDesc('debut_session')
            ->first();
    }

    public function evaluationsDeSession(int $sessionId): Collection
    {
        return Evaluation::query()
            ->with(['agent:id,matricule,nom,prenom,statut'])
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get();
    }

    public function agentsSansN1(): Collection
    {
        return $this->queryAgents([], true)
            ->with(['affectationActive'])
            ->where(function (Builder $query) {
                $query->whereDoesntHave('affectationActive')
                    ->orWhereHas('affectationActive', fn (Builder $q) => $q->whereNull('superieur_hierarchique_id'));
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'matricule', 'nom', 'prenom', 'statut']);
    }

    public function dossiersIncomplets(): Collection
    {
        return $this->queryAgents([], true)
            ->where(function (Builder $query) {
                $query->whereDoesntHave('informationsPersonnelles')
                    ->orWhereDoesntHave('informationsProfessionnelles')
                    ->orWhereDoesntHave('contactsUrgence');
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'matricule', 'nom', 'prenom', 'statut']);
    }

    public function contratsEcheance(int $jours): Collection
    {
        $aujourdhui = now()->startOfDay();

        return Contrat::query()
            ->with(['agent:id,matricule,nom,prenom,statut', 'typeContrat:id,nom'])
            ->where('statut', 'actif')
            ->whereNotNull('date_fin')
            ->whereDate('date_fin', '>=', $aujourdhui->toDateString())
            ->whereDate('date_fin', '<=', $aujourdhui->copy()->addDays($jours)->toDateString())
            ->orderBy('date_fin')
            ->get();
    }

    public function dernierLotCloture(): ?PaieLot
    {
        return PaieLot::query()
            ->where('statut', StatutPaieLot::CLOTURE)
            ->orderByDesc('annee')
            ->orderByDesc('mois')
            ->first();
    }

    private function queryAgents(array $filters, bool $presentOnly): Builder
    {
        $query = Agent::query();

        if ($presentOnly) {
            $query->whereIn('statut', StatutAgent::effectifPresent());
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        $this->appliquerStructure($query, $filters);

        return $query;
    }

    private function appliquerStructure(Builder $query, array $filters): void
    {
        $bureauId = isset($filters['bureau_id']) ? (int) $filters['bureau_id'] : 0;
        $serviceId = isset($filters['service_id']) ? (int) $filters['service_id'] : 0;
        $directionId = isset($filters['direction_id']) ? (int) $filters['direction_id'] : 0;

        if ($bureauId === 0 && $serviceId === 0 && $directionId === 0) {
            return;
        }

        $query->whereHas('affectationActive', function (Builder $q) use ($bureauId, $serviceId, $directionId) {
            $q->where('statut', StatutAffectation::ACTIVE);

            if ($bureauId > 0) {
                $q->where('structurable_type', Bureau::class)->where('structurable_id', $bureauId);

                return;
            }

            if ($serviceId > 0) {
                $bureauIds = Bureau::query()->where('service_id', $serviceId)->pluck('id');
                $q->where(function (Builder $inner) use ($serviceId, $bureauIds) {
                    $inner->where(function (Builder $service) use ($serviceId) {
                        $service->where('structurable_type', Service::class)
                            ->where('structurable_id', $serviceId);
                    })->orWhere(function (Builder $bureau) use ($bureauIds) {
                        $bureau->where('structurable_type', Bureau::class)
                            ->whereIn('structurable_id', $bureauIds);
                    });
                });

                return;
            }

            $serviceIds = Service::query()->where('direction_id', $directionId)->pluck('id');
            $bureauIds = Bureau::query()->whereIn('service_id', $serviceIds)->pluck('id');
            $q->where(function (Builder $inner) use ($directionId, $serviceIds, $bureauIds) {
                $inner->where(function (Builder $direction) use ($directionId) {
                    $direction->where('structurable_type', Direction::class)
                        ->where('structurable_id', $directionId);
                })->orWhere(function (Builder $service) use ($serviceIds) {
                    $service->where('structurable_type', Service::class)
                        ->whereIn('structurable_id', $serviceIds);
                })->orWhere(function (Builder $bureau) use ($bureauIds) {
                    $bureau->where('structurable_type', Bureau::class)
                        ->whereIn('structurable_id', $bureauIds);
                });
            });
        });
    }

    private function chargerStructures(EloquentCollection|Collection $agents): void
    {
        if ($agents->isEmpty()) {
            return;
        }

        $collection = $agents instanceof EloquentCollection
            ? $agents
            : new EloquentCollection($agents->all());

        $collection->load(['affectationActive.structure' => function (MorphTo $morph) {
            $morph->constrain([
                Direction::class => fn ($q) => $q->select(['id', 'nom', 'sigle']),
                Service::class => fn ($q) => $q->select(['id', 'nom', 'sigle', 'direction_id'])->with('direction:id,nom,sigle'),
                Bureau::class => fn ($q) => $q->select(['id', 'nom', 'sigle', 'service_id'])->with('service:id,nom,sigle,direction_id', 'service.direction:id,nom,sigle'),
            ]);
        }]);
    }
}
