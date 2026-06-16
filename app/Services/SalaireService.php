<?php

namespace App\Services;

use App\Interfaces\ClassegrillesalarialeInterface;
use App\Interfaces\ParametregrileInterface;
use App\Interfaces\SalaireInterface;
use Illuminate\Support\Collection;

class SalaireService extends BaseService
{
    /**
     * Indices de base par coefficient (issue du barème FP Congo).
     * Clé = coefficient de la classe, valeur = indice de départ (échelon 0).
     * Formule : indice(E) = BASE_INDICES[coeff] + (E × coeff)
     */
    private const BASE_INDICES = [
        45  => 445,
        50  => 540,
        55  => 645,
        60  => 760,
        75  => 895,
        90  => 1060,
        105 => 1255,
        120 => 1480,
        145 => 2035,
        170 => 2690,
    ];

    public function __construct(
        SalaireInterface $repository,
        private readonly ClassegrillesalarialeInterface $classegrille,
        private readonly ParametregrileInterface $parametregrille,
    ) {
        parent::__construct($repository);
    }

    /**
     * Génère la grille salariale complète (10 classes × 12 échelons = 120 lignes).
     *
     * @param  float|null  $valeurPointIndice  Valeur du point d'indice fournie par l'appelant.
     *                                         Si null, utilise la valeur stockée dans parametregrilles.
     */
    public function generateGrille(?float $valeurPointIndice = null): int
    {
        $pointIndice = $valeurPointIndice
            ?? $this->parametregrille->getCurrent()->valeur_point_indice;

        $classes = $this->classegrille->getAll();
        $now     = now()->toDateTimeString();
        $lignes  = [];

        foreach ($classes as $classe) {
            if (! isset(self::BASE_INDICES[$classe->coefficient])) {
                continue;
            }

            $indiceBase = self::BASE_INDICES[$classe->coefficient];

            for ($echelon = 1; $echelon <= 12; $echelon++) {
                $indice   = $indiceBase + ($echelon * $classe->coefficient);
                $lignes[] = [
                    'classegrillesalariale_id' => $classe->id,
                    'echelon'                  => $echelon,
                    'indice'                   => $indice,
                    'salaire'                  => round($indice * $pointIndice, 2),
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }
        }

        $this->repository->generateGrille($lignes);

        return count($lignes);
    }

    public function getGrille(): Collection
    {
        return $this->repository->getAllWithClasse();
    }
}
