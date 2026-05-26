<?php

namespace App\Services;

use App\Interfaces\DiplomeInterface;

class DiplomeService extends BaseService
{
    public function __construct(DiplomeInterface $repository)
    {
        parent::__construct($repository);
    }
}
