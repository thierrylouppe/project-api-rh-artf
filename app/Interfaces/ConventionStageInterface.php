<?php

namespace App\Interfaces;

use App\Enums\StatutConventionStage;
use App\Models\ConventionStage;
use Illuminate\Support\Collection;

interface ConventionStageInterface extends BaseInterface
{
    public function getEnCours(): Collection;

    public function getProchesEcheance(int $joursAvant): Collection;

    public function changerStatut(int $id, StatutConventionStage $statut): ConventionStage;

    public function findByDossier(int $dossierId): ?ConventionStage;
}
