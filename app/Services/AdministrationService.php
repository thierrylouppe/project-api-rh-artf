<?php

namespace App\Services;

use App\Interfaces\AdministrationInterface;
use Illuminate\Support\Collection;

class AdministrationService extends BaseService
{
    public function __construct(AdministrationInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByLocalite(int $localiteId): Collection
    {
        return $this->repository->getByLocalite($localiteId);
    }
}
