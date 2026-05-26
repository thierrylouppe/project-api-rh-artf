<?php

namespace App\Services;

use App\Interfaces\FonctionInterface;

class FonctionService extends BaseService
{
    public function __construct(FonctionInterface $repository)
    {
        parent::__construct($repository);
    }
}
