<?php

namespace App\Interfaces;

use App\Enums\StatutDossier;
use App\Models\DossierIntegration;
use Illuminate\Support\Collection;

interface DossierIntegrationInterface extends BaseInterface
{
    public function findByReference(string $reference): ?DossierIntegration;

    public function getByStatut(StatutDossier $statut): Collection;

    public function changerStatut(int $id, StatutDossier $nouveauStatut): DossierIntegration;

    public function dernierNumeroReference(int $annee): int;
}
