<?php

namespace App\Repositories;

use App\Interfaces\ServiceInterface;
use App\Models\Service;
use Illuminate\Support\Collection;

class ServiceRepository extends BaseRepository implements ServiceInterface
{
    protected function model(): string
    {
        return Service::class;
    }

    public function getByDirection(int $directionId): Collection
    {
        return Service::where('direction_id', $directionId)->get();
    }
}
