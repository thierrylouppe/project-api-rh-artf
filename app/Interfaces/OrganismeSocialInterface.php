<?php

namespace App\Interfaces;

use App\Models\OrganismeSocial;

interface OrganismeSocialInterface extends BaseInterface
{
    public function existsByNom(string $nom, ?int $excludeId = null): bool;

    public function findByCode(string $code): ?OrganismeSocial;
}
