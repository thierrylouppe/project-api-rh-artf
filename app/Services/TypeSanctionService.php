<?php

namespace App\Services;

use App\Interfaces\SanctionInterface;
use App\Interfaces\TypeSanctionInterface;
use App\Models\TypeSanction;

/** @property TypeSanctionInterface $repository */
class TypeSanctionService extends BaseService
{
    public function __construct(
        TypeSanctionInterface $repository,
        private readonly SanctionInterface $sanctionRepository,
    ) {
        parent::__construct($repository);
    }

    public function delete(int $id): bool
    {
        $type = $this->repository->findById($id);

        abort_if(
            $type instanceof TypeSanction && $type->estCcn(),
            422,
            'Impossible de supprimer un type prévu par la convention collective (art. 90).'
        );

        abort_if(
            $this->sanctionRepository->existsByType($id),
            422,
            'Impossible de supprimer un type de sanction déjà utilisé.'
        );

        return $this->repository->delete($id);
    }
}
