<?php

namespace App\Services;

use App\Interfaces\BureauInterface;
use Illuminate\Support\Collection;

class BureauService extends BaseService
{
    public function __construct(BureauInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getByService(int $serviceId): Collection
    {
        return $this->repository->getByService($serviceId);
    }
}
