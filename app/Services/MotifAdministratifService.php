<?php

namespace App\Services;

use App\Interfaces\MotifAdministratifInterface;

class MotifAdministratifService extends BaseService
{
    public function __construct(MotifAdministratifInterface $repository)
    {
        parent::__construct($repository);
    }
}
