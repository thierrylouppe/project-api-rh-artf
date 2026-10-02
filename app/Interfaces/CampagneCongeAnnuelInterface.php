<?php

namespace App\Interfaces;

use App\Models\CampagneCongeAnnuel;

interface CampagneCongeAnnuelInterface extends BaseInterface
{
    public function findByAnnee(int $annee): ?CampagneCongeAnnuel;

    public function findOuverte(): ?CampagneCongeAnnuel;
}
