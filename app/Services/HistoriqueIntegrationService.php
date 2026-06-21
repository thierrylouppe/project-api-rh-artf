<?php

namespace App\Services;

use App\Interfaces\HistoriqueIntegrationInterface;
use App\Models\HistoriqueIntegration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class HistoriqueIntegrationService extends BaseService
{
    public function __construct(HistoriqueIntegrationInterface $repository)
    {
        parent::__construct($repository);
    }

    public function enregistrer(
        string $type,
        int $id,
        string $action,
        ?array $ancienneValeur = null,
        ?array $nouvelleValeur = null,
        ?string $commentaire = null
    ): HistoriqueIntegration {
        return $this->repository->enregistrer(
            $type,
            $id,
            Auth::id(),
            $action,
            $ancienneValeur,
            $nouvelleValeur,
            $commentaire
        );
    }

    public function getHistorique(string $type, int $id): Collection
    {
        return $this->repository->getHistorique($type, $id);
    }
}
