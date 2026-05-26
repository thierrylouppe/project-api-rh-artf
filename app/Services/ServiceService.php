<?php

namespace App\Services;

use App\Interfaces\ServiceInterface;
use Illuminate\Support\Collection;

class ServiceService extends BaseService
{
    public function __construct(ServiceInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByDirection(int $directionId): Collection
    {
        return $this->repository->getByDirection($directionId);
    }
}
