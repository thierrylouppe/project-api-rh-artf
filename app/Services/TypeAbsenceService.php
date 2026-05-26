<?php

namespace App\Services;

use App\Interfaces\TypeAbsenceInterface;

class TypeAbsenceService extends BaseService
{
    public function __construct(TypeAbsenceInterface $repository)
    {
        parent::__construct($repository);
    }
}
