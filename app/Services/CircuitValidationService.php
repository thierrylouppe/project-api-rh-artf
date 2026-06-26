<?php

namespace App\Services;

use App\Enums\NiveauValidation;
use App\Interfaces\CircuitValidationInterface;
use App\Models\CircuitValidationTypeIntegration;
use App\Models\TypeIntegration;
use Illuminate\Support\Collection;

/** @property CircuitValidationInterface $repository */
class CircuitValidationService extends BaseService
{
    public function __construct(CircuitValidationInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getPourType(int $typeIntegrationId): Collection
    {
        return $this->repository->getPourType($typeIntegrationId);
    }

    /**
     * Remplace intégralement le circuit d'un type d'intégration.
     * Les niveaux sont fournis dans l'ordre souhaité ; l'ordre est recalculé automatiquement.
     *
     * @param  array<int, string>  $niveaux  Valeurs d'enum (ex. ['chef_bureau', 'drh'])
     */
    public function remplacerCircuit(int $typeIntegrationId, array $niveaux): Collection
    {
        $this->validerNiveaux($niveaux);

        TypeIntegration::findOrFail($typeIntegrationId);

        return $this->repository->remplacerCircuit($typeIntegrationId, $niveaux);
    }

    public function ajouterNiveau(int $typeIntegrationId, string $niveau, int $ordre): CircuitValidationTypeIntegration
    {
        $this->validerNiveaux([$niveau]);

        abort_if(
            $this->repository->niveauExiste($typeIntegrationId, $niveau),
            422,
            "Le niveau « {$niveau} » est déjà présent dans le circuit."
        );

        TypeIntegration::findOrFail($typeIntegrationId);

        return $this->repository->ajouterNiveau($typeIntegrationId, $niveau, $ordre);
    }

    public function supprimerNiveau(int $id): void
    {
        $this->repository->supprimerNiveau($id);
    }

    private function validerNiveaux(array $niveaux): void
    {
        $valides = array_column(NiveauValidation::cases(), 'value');

        foreach ($niveaux as $niveau) {
            abort_unless(
                in_array($niveau, $valides, true),
                422,
                "Niveau inconnu : « {$niveau} ». Valeurs acceptées : " . implode(', ', $valides)
            );
        }
    }
}
