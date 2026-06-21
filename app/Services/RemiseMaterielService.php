<?php

namespace App\Services;

use App\Interfaces\RemiseMaterielInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RemiseMaterielService extends BaseService
{
    public function __construct(RemiseMaterielInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['remis_par'] = $data['remis_par'] ?? Auth::id();

        return $data;
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }
}
