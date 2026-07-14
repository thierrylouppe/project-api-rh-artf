<?php

namespace App\Repositories;

use App\Interfaces\DiplomeInterface;
use App\Models\Diplome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DiplomeRepository extends BaseRepository implements DiplomeInterface
{
    protected function model(): string
    {
        return Diplome::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Diplome::with(['classeGrille.categorie', 'classeGrille.grade']);

        if (method_exists(Diplome::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function findById(int $id): Model
    {
        return Diplome::with(['classeGrille.categorie', 'classeGrille.grade'])
            ->findOrFail($id);
    }

    public function create(array $data): Model
    {
        $diplome = parent::create($data);

        return $diplome->load(['classeGrille.categorie', 'classeGrille.grade']);
    }

    public function update(int $id, array $data): Model
    {
        $diplome = parent::update($id, $data);

        return $diplome->load(['classeGrille.categorie', 'classeGrille.grade']);
    }
}
