<?php

namespace App\Repositories;

use App\Interfaces\AyantDroitPieceInterface;
use App\Models\AyantDroitPiece;
use Illuminate\Support\Collection;

class AyantDroitPieceRepository extends BaseRepository implements AyantDroitPieceInterface
{
    protected function model(): string
    {
        return AyantDroitPiece::class;
    }

    public function getByAyantDroit(int $ayantDroitId): Collection
    {
        return AyantDroitPiece::query()
            ->where('ayant_droit_id', $ayantDroitId)
            ->with('uploader:id,name')
            ->orderBy('created_at')
            ->get();
    }

    public function findForAyantDroit(int $ayantDroitId, int $id): AyantDroitPiece
    {
        return AyantDroitPiece::query()
            ->where('ayant_droit_id', $ayantDroitId)
            ->with('uploader:id,name')
            ->findOrFail($id);
    }
}
