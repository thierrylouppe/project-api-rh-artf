<?php

namespace App\Services;

use App\Interfaces\StructureSanitaireInterface;

/** @property StructureSanitaireInterface $repository */
class StructureSanitaireService extends BaseService
{
    public function __construct(StructureSanitaireInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $this->assertNomUnique($data['nom']);

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        if (isset($data['nom'])) {
            $this->assertNomUnique($data['nom'], $id);
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        abort_if(
            $this->repository->estUtilisee($id),
            422,
            'Impossible de supprimer une structure déjà utilisée.'
        );

        return parent::delete($id);
    }

    private function assertNomUnique(string $nom, ?int $excludeId = null): void
    {
        abort_if(
            $this->repository->existsByNom($nom, $excludeId),
            422,
            'Une structure sanitaire porte déjà ce nom.'
        );
    }
}
