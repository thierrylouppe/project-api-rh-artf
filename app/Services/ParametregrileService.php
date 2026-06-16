<?php

namespace App\Services;

use App\Interfaces\ParametregrileInterface;
use Illuminate\Database\Eloquent\Model;

class ParametregrileService extends BaseService
{
    public function __construct(ParametregrileInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getCurrent(): Model
    {
        return $this->repository->getCurrent();
    }
}
