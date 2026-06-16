<?php

namespace App\Repositories;

use App\Interfaces\SalaireInterface;
use App\Models\Salaire;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalaireRepository extends BaseRepository implements SalaireInterface
{
    protected function model(): string
    {
        return Salaire::class;
    }

    /**
     * Bulk insert avec transaction : supprime l'ancienne grille puis insère les nouvelles lignes.
     * Utilise insert() au lieu d'un loop create() pour de meilleures performances (120 lignes → 1 requête).
     */
    public function generateGrille(array $salaries): void
    {
        DB::transaction(function () use ($salaries) {
            Salaire::query()->delete();
            Salaire::insert($salaries);
        });
    }

    public function getAllWithClasse(): Collection
    {
        return Salaire::with(['classe.categorie', 'classe.grade'])
            ->orderBy('classegrillesalariale_id')
            ->orderBy('echelon')
            ->get();
    }
}
