<?php

namespace App\Services;

use App\Interfaces\TypeCongeInterface;

class TypeCongeService extends BaseService
{
    public function __construct(TypeCongeInterface $repository)
    {
        parent::__construct($repository);
    }
}
