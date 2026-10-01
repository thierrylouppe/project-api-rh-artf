<?php

namespace App\Repositories;

use App\Interfaces\StructureSanitaireInterface;
use App\Models\ArretSante;
use App\Models\PriseEnCharge;
use App\Models\StructureSanitaire;
use App\Models\VisiteMedicale;
use Illuminate\Support\Collection;

class StructureSanitaireRepository extends BaseRepository implements StructureSanitaireInterface
{
    protected function model(): string
    {
        return StructureSanitaire::class;
    }

    public function getAll(array $filters = []): Collection
    {
        if (! array_key_exists('actif', $filters)) {
            $filters['actif'] = true;
        } elseif ($filters['actif'] === 'all') {
            unset($filters['actif']);
        }

        $query = StructureSanitaire::query();

        if (method_exists(StructureSanitaire::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('nom')->get();
    }

    public function existsByNom(string $nom, ?int $excludeId = null): bool
    {
        return StructureSanitaire::query()
            ->where('nom', $nom)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function estUtilisee(int $id): bool
    {
        return VisiteMedicale::query()->where('structure_sanitaire_id', $id)->exists()
            || PriseEnCharge::query()->where('structure_sanitaire_id', $id)->exists()
            || ArretSante::query()->where('structure_sanitaire_id', $id)->exists();
    }
}
