<?php

namespace App\Services;

use App\Interfaces\DirectionInterface;
use Illuminate\Support\Collection;

class DirectionService extends BaseService
{
    public function __construct(DirectionInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByAdministration(int $administrationId): Collection
    {
        return $this->repository->getByAdministration($administrationId);
    }
}
