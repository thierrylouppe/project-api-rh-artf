<?php

namespace App\Services;

use App\Enums\TypeOrganismeSocial;
use App\Interfaces\AffiliationSocialeInterface;
use App\Interfaces\OrganismeSocialInterface;
use App\Models\OrganismeSocial;

/** @property OrganismeSocialInterface $repository */
class OrganismeSocialService extends BaseService
{
    public function __construct(
        OrganismeSocialInterface $repository,
        private readonly AffiliationSocialeInterface $affiliationRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $this->assertNomUnique($data['nom']);

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $organisme = $this->repository->findById($id);

        if (isset($data['nom'])) {
            $this->assertNomUnique($data['nom'], $id);
        }

        if ($organisme instanceof OrganismeSocial && $organisme->estSysteme()) {
            if (array_key_exists('type', $data)) {
                $type = $data['type'] instanceof TypeOrganismeSocial
                    ? $data['type']
                    : TypeOrganismeSocial::from((string) $data['type']);
                abort_if($type !== TypeOrganismeSocial::CNSS, 422, 'Le type de la CNSS ne peut pas être modifié.');
            }
            if (array_key_exists('code', $data) && $data['code'] !== 'CNSS') {
                abort(422, 'Le code de la CNSS ne peut pas être modifié.');
            }
        }

        return $data;
    }

    public function delete(int $id): bool
    {
        $organisme = $this->repository->findById($id);

        abort_if(
            $organisme instanceof OrganismeSocial && $organisme->estSysteme(),
            422,
            'Impossible de supprimer l\'organisme CNSS.'
        );

        abort_if(
            $this->affiliationRepository->getAll(['organisme_id' => $id])->isNotEmpty(),
            422,
            'Impossible de supprimer un organisme déjà utilisé par une affiliation.'
        );

        return $this->repository->delete($id);
    }

    private function assertNomUnique(string $nom, ?int $excludeId = null): void
    {
        abort_if(
            $this->repository->existsByNom($nom, $excludeId),
            422,
            'Un organisme porte déjà ce nom.'
        );
    }
}
