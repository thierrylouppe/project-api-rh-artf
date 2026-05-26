<?php

namespace App\Services;

use App\Interfaces\ParametreApplicationInterface;
use App\Models\ParametreApplication;

class ParametreApplicationService extends BaseService
{
    public function __construct(ParametreApplicationInterface $repository)
    {
        parent::__construct($repository);
    }

    public function get(string $cle): ?string
    {
        return $this->repository->findByCle($cle)?->valeur;
    }

    public function set(string $cle, mixed $valeur, ?string $description = null): ParametreApplication
    {
        $param = $this->repository->findByCle($cle);

        if ($param) {
            return $this->repository->update($param->id, [
                'valeur' => (string) $valeur,
                'description' => $description ?? $param->description,
            ]);
        }

        return $this->repository->create([
            'cle' => $cle,
            'valeur' => (string) $valeur,
            'description' => $description,
        ]);
    }
}
