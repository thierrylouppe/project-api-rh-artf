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

        $cles = $this->clesFeriees($start, $end);

        return collect(CarbonPeriod::create($start, $end))
            ->filter(fn (Carbon $jour) => $this->estOuvrable($jour, $cles))
            ->count();
    }

    /**
     * À partir d'une date de départ ouvrable et d'un nombre de jours,
     * calcule le dernier jour de congé et le jour de reprise.
     *
     * @return array{date_fin: string, date_reprise: string}
     */
    public function calculerEcheance(string $dateDepart, int $joursOuvrables): array
    {
        abort_if($joursOuvrables < 1, 422, 'Aucun jour de congé annuel disponible.');

        $depart  = Carbon::parse($dateDepart)->startOfDay();
        $horizon = $depart->copy()->addDays(400);
        $cles    = $this->clesFeriees($depart, $horizon);

        abort_unless(
            $this->estOuvrable($depart, $cles),
            422,
            'La date de départ doit être un jour ouvrable.'
        );

        $fin      = $depart->copy();
        $restants = $joursOuvrables;
        $garde    = 0;
        while ($restants > 1) {
            $fin->addDay();
            if ($this->estOuvrable($fin, $cles)) {
                $restants--;
            }
            abort_if(++$garde > 400, 422, 'Impossible de calculer la fin du congé.');
        }

        $reprise = $fin->copy()->addDay();
        $garde   = 0;
        while (! $this->estOuvrable($reprise, $cles)) {
            $reprise->addDay();
            abort_if(++$garde > 20, 422, 'Impossible de calculer la date de reprise.');
        }

        return [
            'date_fin'     => $fin->format('Y-m-d'),
            'date_reprise' => $reprise->format('Y-m-d'),
        ];
    }

    /** @param list<string> $clesFeriees */
    private function estOuvrable(CarbonInterface $jour, array $clesFeriees): bool
    {
        return ! $jour->isWeekend() && ! in_array($jour->format('Y-m-d'), $clesFeriees, true);
    }

    /** @return list<string> */
    private function clesFeriees(CarbonInterface $start, CarbonInterface $end): array
    {
        return $this->repository->datesFeriees($start, $end)
            ->flatMap(function ($ferie) use ($start, $end) {
                if ($ferie->recurrent) {
                    $dates = [];
                    for ($annee = $start->year; $annee <= $end->year; $annee++) {
                        $dates[] = $ferie->date->copy()->year($annee)->format('Y-m-d');
                    }

                    return $dates;
                }

                return [$ferie->date->format('Y-m-d')];
            })
            ->unique()
            ->values()
            ->all();
    }
}
