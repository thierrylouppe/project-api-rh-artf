<?php

namespace App\Repositories;

use App\Interfaces\TypeIntegrationInterface;
use App\Models\TypeIntegration;

class TypeIntegrationRepository extends BaseRepository implements TypeIntegrationInterface
{
    protected function model(): string
    {
        return TypeIntegration::class;
    }
}
