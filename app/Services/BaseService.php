<?php

namespace App\Services;

use App\Interfaces\BaseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseService
{
    public function __construct(protected BaseInterface $repository) {}

    public function getAll(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }

    public function findById(int $id): Model
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Model
    {
        $data = $this->beforeCreate($data);

        $model = $this->repository->create($data);

        return $this->afterCreate($model);
    }

    public function update(int $id, array $data): Model
    {
        $data = $this->beforeUpdate($id, $data);

        $model = $this->repository->update($id, $data);

        return $this->afterUpdate($model);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    protected function beforeCreate(array $data): array
    {
        return $data;
    }

    protected function afterCreate(Model $model): Model
    {
        return $model;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        return $data;
    }

    protected function afterUpdate(Model $model): Model
    {
        return $model;
    }
}
