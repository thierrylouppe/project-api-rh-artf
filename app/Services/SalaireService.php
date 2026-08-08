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
     * Génère la grille salariale complète (classes × plage d'échelons).
     *
     * Plage lue depuis parametregrilles (`echelon_depart` → `echelon_fin`).
     * Les indices de base restent le barème FP Congo (BASE_INDICES) :
     * `indice_base` / `ecart_depart` en base sont conservés pour affichage / évolution future,
     * mais n'entrent pas dans la formule (contrat de sortie inchangé).
     *
     * @param  float|null  $valeurPointIndice  Valeur du point d'indice fournie par l'appelant.
     *                                         Si null, utilise la valeur stockée dans parametregrilles.
     * @return array{total: int, valeur_point_indice: float, echelon_depart: int, echelon_fin: int}
     */
    public function generateGrille(?float $valeurPointIndice = null): array
    {
        $params = $this->parametregrille->getCurrent();

        $pointIndice = (float) ($valeurPointIndice ?? $params->valeur_point_indice);
        $echelonDepart = max(1, (int) $params->echelon_depart);
        $echelonFin = max($echelonDepart, (int) $params->echelon_fin);

        $classes = $this->classegrille->getAll();
        $now     = now()->toDateTimeString();
        $lignes  = [];

        foreach ($classes as $classe) {
            if (! isset(self::BASE_INDICES[$classe->coefficient])) {
                continue;
            }

            $indiceBase = self::BASE_INDICES[$classe->coefficient];

            for ($echelon = $echelonDepart; $echelon <= $echelonFin; $echelon++) {
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

        return [
            'total'               => count($lignes),
            'valeur_point_indice' => $pointIndice,
            'echelon_depart'      => $echelonDepart,
            'echelon_fin'         => $echelonFin,
        ];
    }

    public function getGrille(): Collection
    {
        return $this->repository->getAllWithClasse();
    }
}
