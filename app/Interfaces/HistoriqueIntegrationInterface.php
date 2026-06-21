<?php

namespace App\Interfaces;

use App\Models\HistoriqueIntegration;
use Illuminate\Support\Collection;

interface HistoriqueIntegrationInterface extends BaseInterface
{
    public function enregistrer(
        string $type,
        int $id,
        int $utilisateurId,
        string $action,
        ?array $ancienneValeur,
        ?array $nouvelleValeur,
        ?string $commentaire
    ): HistoriqueIntegration;

    public function getHistorique(string $type, int $id): Collection;
}
