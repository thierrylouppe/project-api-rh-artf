<?php

namespace App\Repositories;

use App\Enums\StatutConventionStage;
use App\Interfaces\ConventionStageInterface;
use App\Models\ConventionStage;
use Illuminate\Support\Collection;

class ConventionStageRepository extends BaseRepository implements ConventionStageInterface
{
    protected function model(): string
    {
        return ConventionStage::class;
    }

    public function getEnCours(): Collection
    {
        return ConventionStage::where('statut_stage', StatutConventionStage::EN_COURS)->get();
    }

    public function getProchesEcheance(int $joursAvant): Collection
    {
        $cible = now()->addDays($joursAvant)->toDateString();

        return ConventionStage::where('statut_stage', StatutConventionStage::EN_COURS)
            ->whereDate('date_fin', '=', $cible)
            ->with(['agent', 'tuteurInterne'])
            ->get();
    }

    public function changerStatut(int $id, StatutConventionStage $statut): ConventionStage
    {
        $convention = $this->findById($id);
        $convention->update(['statut_stage' => $statut->value]);

        return $convention->fresh();
    }

    public function findByDossier(int $dossierId): ?ConventionStage
    {
        return ConventionStage::where('dossier_integration_id', $dossierId)->latest()->first();
    }
}
