<?php

namespace App\Services;

use App\Enums\TypeActionFormation;
use App\Interfaces\CatalogueFormationInterface;
use App\Interfaces\InscriptionFormationInterface;

/** @property CatalogueFormationInterface $repository */
class CatalogueFormationService extends BaseService
{
    public function __construct(
        CatalogueFormationInterface $repository,
        private readonly InscriptionFormationInterface $inscriptionRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $this->assertTitreUnique($data['titre']);
        $this->assertDureeCcn($data);

        $data['anciennete_min_ans'] = $data['anciennete_min_ans'] ?? 3;

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        if (isset($data['titre'])) {
            $this->assertTitreUnique($data['titre'], $id);
        }

        $formation = $this->repository->findById($id);
        $this->assertDureeCcn(array_merge([
            'type_action' => $formation->type_action?->value,
            'duree_jours' => $formation->duree_jours,
        ], $data));

        return $data;
    }

    public function delete(int $id): bool
    {
        abort_if(
            $this->inscriptionRepository->getAll(['formation_id' => $id])->isNotEmpty(),
            422,
            'Impossible de supprimer une formation déjà utilisée par une inscription.'
        );

        return $this->repository->delete($id);
    }

    private function assertTitreUnique(string $titre, ?int $excludeId = null): void
    {
        abort_if(
            $this->repository->existsByTitre($titre, $excludeId),
            422,
            'Une formation porte déjà ce titre.'
        );
    }

    private function assertDureeCcn(array $data): void
    {
        if (! isset($data['type_action'], $data['duree_jours'])) {
            return;
        }

        $type = $data['type_action'] instanceof TypeActionFormation
            ? $data['type_action']
            : TypeActionFormation::from((string) $data['type_action']);
        $maxMois = $type->dureeMaxMois();

        if ($maxMois === null) {
            return;
        }

        abort_if(
            (int) $data['duree_jours'] > $maxMois * 31,
            422,
            "La durée de cette action ne peut pas excéder {$maxMois} mois (CCN art. 99 / 102)."
        );
    }
}
