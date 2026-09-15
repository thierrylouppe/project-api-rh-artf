<?php

namespace App\Interfaces;

interface CatalogueFormationInterface extends BaseInterface
{
    public function existsByTitre(string $titre, ?int $excludeId = null): bool;
}
