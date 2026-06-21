<?php

namespace App\Repositories;

use App\Interfaces\RemiseMaterielInterface;
use App\Models\RemiseMateriel;
use Illuminate\Support\Collection;

class RemiseMaterielRepository extends BaseRepository implements RemiseMaterielInterface
{
    protected function model(): string
    {
        return RemiseMateriel::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return RemiseMateriel::where('agent_id', $agentId)->get();
    }
}
