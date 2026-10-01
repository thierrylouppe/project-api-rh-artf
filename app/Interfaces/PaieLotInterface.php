<?php

namespace App\Interfaces;

use App\Models\PaieLot;
use App\Models\PaieLotLigne;
use Illuminate\Support\Collection;

interface PaieLotInterface extends BaseInterface
{
    public function findByPeriode(int $annee, int $mois): ?PaieLot;

    public function getLignes(int $lotId, array $filters = []): Collection;

    public function getLigne(int $lotId, int $ligneId): PaieLotLigne;

    public function getLignesClotureesParAgent(int $agentId): Collection;

    public function supprimerLignes(int $lotId): void;

    public function creerLigne(array $data): PaieLotLigne;

    /** @param  list<array<string, mixed>>  $details */
    public function creerDetails(int $ligneId, array $details): void;

    public function updateLigne(int $ligneId, array $data): void;

    public function existeSnapshotVerrouille(int $agentId, int $elementId): bool;

    public function existeSnapshotElementVerrouille(int $elementId): bool;
}
