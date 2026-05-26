<?php

namespace App\Services;

use App\Interfaces\GradeInterface;

class GradeService extends BaseService
{
    public function __construct(GradeInterface $repository)
    {
        parent::__construct($repository);
    }
}
