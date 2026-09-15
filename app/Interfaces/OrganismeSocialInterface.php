<?php

namespace App\Interfaces;

interface OrganismeSocialInterface extends BaseInterface
{
    public function existsByNom(string $nom, ?int $excludeId = null): bool;
}
