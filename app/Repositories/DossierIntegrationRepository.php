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
}
