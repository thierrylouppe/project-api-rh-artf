<?php

namespace App\Interfaces;

use App\Models\PlanFormation;
use App\Models\PlanFormationLigne;
use Illuminate\Support\Collection;

interface PlanFormationInterface extends BaseInterface
{
    public function findByAnnee(int $annee): ?PlanFormation;

    public function getLignes(int $planId): Collection;

    public function ajouterLigne(int $planId, array $data): PlanFormationLigne;

    public function trouverLigne(int $planId, int $ligneId): PlanFormationLigne;

    public function supprimerLigne(int $ligneId): bool;
}
