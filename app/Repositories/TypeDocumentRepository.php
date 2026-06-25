<?php

namespace App\Repositories;

use App\Interfaces\TypeDocumentInterface;
use App\Models\TypeDocument;
use Illuminate\Support\Collection;

class TypeDocumentRepository extends BaseRepository implements TypeDocumentInterface
{
    protected function model(): string
    {
        return TypeDocument::class;
    }

    public function findByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return TypeDocument::whereIn('id', $ids)->orderBy('nom')->get();
    }

    public function getObligatoires(): Collection
    {
        return TypeDocument::where('obligatoire', true)->orderBy('nom')->get();
    }

    public function getOptionnels(): Collection
    {
        return TypeDocument::where('obligatoire', false)->orderBy('nom')->get();
    }
}
