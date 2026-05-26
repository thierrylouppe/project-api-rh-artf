<?php

namespace App\Repositories;

use App\Interfaces\DirectionInterface;
use App\Models\Direction;
use Illuminate\Support\Collection;

class DirectionRepository extends BaseRepository implements DirectionInterface
{
    protected function model(): string
    {
        return Direction::class;
    }

    public function getByAdministration(int $administrationId): Collection
    {
        return Direction::where('administration_id', $administrationId)->get();
    }
}
