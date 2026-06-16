<?php

namespace App\Services;

use App\Interfaces\ClassegrillesalarialeInterface;

class ClassegrillesalarialeService extends BaseService
{
    public function __construct(ClassegrillesalarialeInterface $repository)
    {
        parent::__construct($repository);
    }
}
