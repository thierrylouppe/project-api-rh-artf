<?php

namespace App\Services;

use App\Interfaces\RegleAcquisitionCongeInterface;

class RegleAcquisitionCongeService extends BaseService
{
    public function __construct(RegleAcquisitionCongeInterface $repository)
    {
        parent::__construct($repository);
    }
}
