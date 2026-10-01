<?php

namespace App\Repositories;

use App\Interfaces\LotNominationInterface;
use App\Models\LotNomination;

class LotNominationRepository extends BaseRepository implements LotNominationInterface
{
    protected function model(): string
    {
        return LotNomination::class;
    }

    public function findWithLignes(int $id): LotNomination
    {
        return LotNomination::query()
            ->with(['nominations.agent', 'nominations.structure', 'validations.validateur'])
            ->findOrFail($id);
    }
}
