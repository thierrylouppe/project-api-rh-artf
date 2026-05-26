<?php

namespace App\Services;

use App\Interfaces\EchelonInterface;

class EchelonService extends BaseService
{
    public function __construct(EchelonInterface $repository)
    {
        parent::__construct($repository);
    }
}
