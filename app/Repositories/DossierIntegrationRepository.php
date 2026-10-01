<?php

namespace App\Repositories;

use App\Enums\StatutDossier;
use App\Interfaces\DossierIntegrationInterface;
use App\Models\DossierIntegration;
use Illuminate\Support\Collection;

class DossierIntegrationRepository extends BaseRepository implements DossierIntegrationInterface
{
    protected function model(): string
    {
        return DossierIntegration::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = DossierIntegration::query()->with('agent');

        if (method_exists(DossierIntegration::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function findByReference(string $reference): ?DossierIntegration
    {
        return DossierIntegration::where('reference', $reference)->first();
    }

    public function getByStatut(StatutDossier $statut): Collection
    {
        return DossierIntegration::where('statut', $statut->value)->get();
    }

    public function changerStatut(int $id, StatutDossier $nouveauStatut): DossierIntegration
    {
        $dossier = $this->findById($id);
        $dossier->update(['statut' => $nouveauStatut->value]);

        return $dossier->fresh();
    }

    public function dernierNumeroReference(int $annee): int
    {
        $dernier = DossierIntegration::whereYear('created_at', $annee)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('reference');

        if (! $dernier) {
            return 0;
        }

        return (int) substr($dernier, -6);
    }

    public function getIntegresNecessitantContrat(): Collection
    {
        return DossierIntegration::query()
            ->where('statut', StatutDossier::INTEGRE->value)
            ->whereHas('typeIntegration', function ($query) {
                $query->where('necessite_contrat', true)
                    ->where('nom', 'not like', 'Stage%');
            })
            ->whereHas('agent', function ($query) {
                $query->whereNotNull('date_prise_service')
                    // 42 j. calendaires ≈ 30 j. ouvrables (week-ends) : le filtre PHP affine.
                    ->whereDate('date_prise_service', '<=', now()->subDays(42)->toDateString());
            })
            ->whereDoesntHave('agent.contrats', function ($query) {
                $query->where('statut', 'actif')
                    ->whereHas('typeContrat', fn ($type) => $type->whereIn('sigle', ['CDI', 'CDD']));
            })
            ->with(['agent', 'typeIntegration'])
            ->get();
    }
}
