<?php

namespace App\Repositories;

use App\Interfaces\JourFerieInterface;
use App\Models\JourFerie;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class JourFerieRepository extends BaseRepository implements JourFerieInterface
{
    protected function model(): string
    {
        return JourFerie::class;
    }

    public function datesFeriees(CarbonInterface $debut, CarbonInterface $fin): Collection
    {
        return JourFerie::query()->get()->filter(function (JourFerie $ferie) use ($debut, $fin) {
            $date = $ferie->date->copy();
            if ($ferie->recurrent) {
                for ($annee = $debut->year; $annee <= $fin->year; $annee++) {
                    $occurence = $date->copy()->year($annee)->startOfDay();
                    if ($occurence->betweenIncluded($debut->copy()->startOfDay(), $fin->copy()->startOfDay())) {
                        return true;
                    }
                }

                return false;
            }

            return $date->betweenIncluded($debut->copy()->startOfDay(), $fin->copy()->startOfDay());
        })->values();
    }
}
