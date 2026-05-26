<?php

namespace App\Services;

use App\Interfaces\LocaliteInterface;

class LocaliteService extends BaseService
{
    public function __construct(LocaliteInterface $repository)
    {
        parent::__construct($repository);
    }
}
