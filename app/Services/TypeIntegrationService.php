<?php

namespace App\Services;

use App\Interfaces\TypeIntegrationInterface;

class TypeIntegrationService extends BaseService
{
    public function __construct(TypeIntegrationInterface $repository)
    {
        parent::__construct($repository);
    }
}
