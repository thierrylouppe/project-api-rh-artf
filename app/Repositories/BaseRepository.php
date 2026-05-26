<?php

namespace App\Repositories;

use App\Interfaces\BaseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseInterface
{
    abstract protected function model(): string;

    public function getAll(array $filters = []): Collection
    {
        $query = $this->model()::query();

        if (method_exists($this->model(), 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function findById(int $id): Model
    {
        return $this->model()::findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model()::create($data);
    }

    public function update(int $id, array $data): Model
    {
        $model = $this->findById($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->findById($id)->delete();
    }
}
