<?php

namespace App\Services;

use App\Interfaces\CategorieInterface;

class CategorieService extends BaseService
{
    public function __construct(CategorieInterface $repository)
    {
        parent::__construct($repository);
    }
}
