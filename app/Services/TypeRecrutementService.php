<?php

namespace App\Services;

use App\Interfaces\TypeRecrutementInterface;

class TypeRecrutementService extends BaseService
{
    public function __construct(TypeRecrutementInterface $repository)
    {
        parent::__construct($repository);
    }
}
