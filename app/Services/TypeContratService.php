<?php

namespace App\Services;

use App\Interfaces\TypeContratInterface;

class TypeContratService extends BaseService
{
    public function __construct(TypeContratInterface $repository)
    {
        parent::__construct($repository);
    }
}
