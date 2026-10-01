<?php

namespace App\Repositories;

use App\Interfaces\ArretSantePieceInterface;
use App\Models\ArretSantePiece;
use Illuminate\Support\Collection;

class ArretSantePieceRepository extends BaseRepository implements ArretSantePieceInterface
{
    protected function model(): string
    {
        return ArretSantePiece::class;
    }

    public function getByArret(int $arretId): Collection
    {
        return ArretSantePiece::query()
            ->where('arret_sante_id', $arretId)
            ->with('uploader:id,name')
            ->orderBy('created_at')
            ->get();
    }

    public function findForArret(int $arretId, int $id): ArretSantePiece
    {
        return ArretSantePiece::query()
            ->where('arret_sante_id', $arretId)
            ->with('uploader:id,name')
            ->findOrFail($id);
    }
}
