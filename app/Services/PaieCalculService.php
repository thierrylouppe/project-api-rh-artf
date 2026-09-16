<?php

namespace App\Services;

use App\Enums\CodePaieElement;

class PaieCalculService
{
    public function arrondirFcfa(float $montant): int
    {
        return (int) round($montant, 0, PHP_ROUND_HALF_UP);
    }

    public function anneesRevolues(?\DateTimeInterface $priseService, \DateTimeInterface $au): int
    {
        if ($priseService === null) {
            return 0;
        }

        return (int) $priseService->diff($au)->y;
    }

    /** Taux art. 56 : 2 % après 2 ans, +1 % par an, plafond 40 %. */
    public function tauxAnciennete(int $anneesRevolues): float
    {
        if ($anneesRevolues < 2) {
            return 0.0;
        }

        return min(0.40, 0.02 + 0.01 * ($anneesRevolues - 2));
    }

    public function montantAnciennete(float $base, int $anneesRevolues): int
    {
        $taux = $this->tauxAnciennete($anneesRevolues);

        if ($taux <= 0 || $base <= 0) {
            return 0;
        }

        return $this->arrondirFcfa($base * $taux);
    }

    public function montantFinAnnee(float $base, int $montantAnciennete): int
    {
        return $this->arrondirFcfa($base) + $montantAnciennete;
    }

    public function montantArbreNoel(float $montantDefaut, int $nbEnfantsEligibles): int
    {
        $parts = $nbEnfantsEligibles === 0 ? 1 : min(3, $nbEnfantsEligibles);

        return $this->arrondirFcfa($montantDefaut * $parts);
    }

    public function montantRentreeScolaire(float $montantDefaut): int
    {
        return $this->arrondirFcfa($montantDefaut);
    }

    public function montantFormationAfrique(float $base): int
    {
        return $this->arrondirFcfa($base * 0.85);
    }

    public function tauxJournalierMissionLocale(?string $sigle, ?int $indice, bool $localiteSecondaire): int
    {
        $categorie = $this->categorieMission($sigle, $indice);

        return match ($categorie) {
            1 => $localiteSecondaire ? 200_000 : 250_000,
            2 => $localiteSecondaire ? 150_000 : 200_000,
            3 => $localiteSecondaire ? 100_000 : 150_000,
            default => $localiteSecondaire ? 80_000 : 100_000,
        };
    }

    public function tauxJournalierMissionEtranger(?string $sigle, bool $afrique): int
    {
        $categorie = $this->categorieMission($sigle, null);

        if ($categorie === 1) {
            return $afrique ? 400_000 : 500_000;
        }
        if ($categorie === 2) {
            return $afrique ? 350_000 : 450_000;
        }

        return $afrique ? 300_000 : 400_000;
    }

    public function categorieMission(?string $sigle, ?int $indice): int
    {
        return match ($sigle) {
            'DG' => 1,
            'DC', 'DD' => 2,
            'CS', 'CB' => 3,
            default => ($indice !== null && $indice >= 1150) ? 3 : 4,
        };
    }

    public function codeSalaireBase(): string
    {
        return 'salaire_base';
    }

    public function estCodeAuto(string $code): bool
    {
        return CodePaieElement::tryFrom($code)?->estCalculeAuto() ?? false;
    }
}
