<?php

namespace App\Interfaces;

use App\Models\CircuitValidationTypeIntegration;
use Illuminate\Support\Collection;

interface CircuitValidationInterface extends BaseInterface
{
    /** Retourne les étapes actives du circuit triées par ordre, pour un type donné. */
    public function getPourType(int $typeIntegrationId): Collection;

    /**
     * Retourne les étapes sous forme de tableau simple prêt à être utilisé
     * par ValidationWorkflowRepository::initialiserCircuit().
     *
     * @return array<int, array{niveau: string, ordre: int}>
     */
    public function getCircuitPourType(int $typeIntegrationId): array;

    /**
     * Remplace intégralement le circuit d'un type d'intégration.
     * Supprime les étapes existantes puis crée les nouvelles.
     *
     * @param  array<int, string>  $niveaux  Valeurs d'enum ordonnées (ex. ['chef_bureau', 'drh'])
     */
    public function remplacerCircuit(int $typeIntegrationId, array $niveaux): Collection;

    public function ajouterNiveau(int $typeIntegrationId, string $niveau, int $ordre): CircuitValidationTypeIntegration;

    public function supprimerNiveau(int $id): void;

    public function niveauExiste(int $typeIntegrationId, string $niveau): bool;
}
