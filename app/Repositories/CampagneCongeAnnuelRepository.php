<?php

namespace App\Repositories;

use App\Enums\StatutCampagneCongeAnnuel;
use App\Interfaces\CampagneCongeAnnuelInterface;
use App\Models\CampagneCongeAnnuel;

class CampagneCongeAnnuelRepository extends BaseRepository implements CampagneCongeAnnuelInterface
{
    protected function model(): string
    {
        return CampagneCongeAnnuel::class;
    }

    public function findByAnnee(int $annee): ?CampagneCongeAnnuel
    {
        return CampagneCongeAnnuel::query()->where('annee', $annee)->first();
    }

    public function findOuverte(): ?CampagneCongeAnnuel
    {
        return CampagneCongeAnnuel::query()
            ->where('statut', StatutCampagneCongeAnnuel::OUVERTE->value)
            ->first();
    }
}
