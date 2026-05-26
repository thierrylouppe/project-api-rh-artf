<?php

namespace App\Repositories;

use App\Interfaces\BureauInterface;
use App\Models\Bureau;
use Illuminate\Support\Collection;

class BureauRepository extends BaseRepository implements BureauInterface
{
    protected function model(): string
    {
        return Bureau::class;
    }

    public function getByService(int $serviceId): Collection
    {
        return Bureau::where('service_id', $serviceId)->get();
    }
}
