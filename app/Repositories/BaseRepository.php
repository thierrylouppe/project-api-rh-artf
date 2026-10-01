<?php

namespace App\Repositories;

use App\Interfaces\BaseInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseInterface
{
    abstract protected function model(): string;

    public function getAll(array $filters = []): Collection
    {
        // Vague F — extraire l'utilisateur de scope injecté par le middleware ScopeByBureau.
        // La clé '_scope_user' est réservée et ne doit pas être transmise au scopeFilter.
        $scopeUser  = $filters['_scope_user']  ?? null;
        $scopeNiveau = $filters['_scope_niveau'] ?? 'service';
        unset($filters['_scope_user'], $filters['_scope_niveau']);

        $query = $this->model()::query();

        if (method_exists($this->model(), 'scopeFilter')) {
            $query->filter($filters);
        }

        // Appliquer le cloisonnement si l'utilisateur est défini et que le modèle le supporte.
        if ($scopeUser instanceof User) {
            if (method_exists($this->model(), 'scopeMaStructure')) {
                // Modèle Agent (ou similaire) : filtré directement
                $query->maStructure($scopeUser, $scopeNiveau);
            } elseif (method_exists($this->model(), 'scopeParMaStructure')) {
                // Modèle avec relation agent() : filtré via la relation
                $query->parMaStructure($scopeUser, $scopeNiveau);
            }
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
