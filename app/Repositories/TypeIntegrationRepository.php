<?php

namespace App\Repositories;

use App\Interfaces\TypeIntegrationInterface;
use App\Models\TypeIntegration;
use Illuminate\Support\Collection;

class TypeIntegrationRepository extends BaseRepository implements TypeIntegrationInterface
{
    protected function model(): string
    {
        return TypeIntegration::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = TypeIntegration::with('documentsObligatoires');

        if (method_exists(TypeIntegration::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }
}
