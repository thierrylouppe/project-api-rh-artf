<?php

namespace App\Services;

use App\Interfaces\JourFerieInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class JourFerieService extends BaseService
{
    public function __construct(JourFerieInterface $repository)
    {
        parent::__construct($repository);
    }

    public function estFerie(CarbonInterface $date): bool
    {
        return $this->repository->datesFeriees($date->copy()->startOfDay(), $date->copy()->startOfDay())->isNotEmpty();
    }

    public function calculerJoursOuvrables(string $debut, string $fin): int
    {
        $start = Carbon::parse($debut)->startOfDay();
        $end   = Carbon::parse($fin)->startOfDay();

        abort_if($end->lt($start), 422, 'La date de fin doit être postérieure ou égale à la date de début.');

        $feries = $this->repository->datesFeriees($start, $end);
        $cles   = $feries->flatMap(function ($ferie) use ($start, $end) {
            if ($ferie->recurrent) {
                $dates = [];
                for ($annee = $start->year; $annee <= $end->year; $annee++) {
                    $dates[] = $ferie->date->copy()->year($annee)->format('Y-m-d');
                }

                return $dates;
            }

            return [$ferie->date->format('Y-m-d')];
        })->unique()->all();

        return collect(CarbonPeriod::create($start, $end))
            ->filter(fn (Carbon $jour) => ! $jour->isWeekend())
            ->filter(fn (Carbon $jour) => ! in_array($jour->format('Y-m-d'), $cles, true))
            ->count();
    }
}
