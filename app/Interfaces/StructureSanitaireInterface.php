<?php

namespace App\Interfaces;

interface StructureSanitaireInterface extends BaseInterface
{
    public function existsByNom(string $nom, ?int $excludeId = null): bool;

    public function estUtilisee(int $id): bool;
}
