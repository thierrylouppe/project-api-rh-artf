<?php

namespace App\Repositories;

use App\Interfaces\LotAffectationInterface;
use App\Models\LotAffectation;

class LotAffectationRepository extends BaseRepository implements LotAffectationInterface
{
    protected function model(): string
    {
        return LotAffectation::class;
    }

    public function findWithLignes(int $id): LotAffectation
    {
        return LotAffectation::query()
            ->with([
                'affectations.agent',
                'affectations.structure',
                'affectations.superieurHierarchique',
                'validations.validateur',
            ])
            ->findOrFail($id);
    }
}
