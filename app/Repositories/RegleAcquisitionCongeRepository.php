<?php

namespace App\Repositories;

use App\Interfaces\RegleAcquisitionCongeInterface;
use App\Models\RegleAcquisitionConge;

class RegleAcquisitionCongeRepository extends BaseRepository implements RegleAcquisitionCongeInterface
{
    protected function model(): string
    {
        return RegleAcquisitionConge::class;
    }

    public function findByTypeConge(int $typeCongeId): ?RegleAcquisitionConge
    {
        return RegleAcquisitionConge::query()->where('type_conge_id', $typeCongeId)->first();
    }
}
