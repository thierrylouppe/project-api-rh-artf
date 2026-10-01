<?php

namespace App\Repositories;

use App\Interfaces\PrestationPieceInterface;
use App\Models\PrestationPiece;
use Illuminate\Support\Collection;

class PrestationPieceRepository extends BaseRepository implements PrestationPieceInterface
{
    protected function model(): string
    {
        return PrestationPiece::class;
    }

    public function getByPrestation(int $prestationId): Collection
    {
        return PrestationPiece::query()
            ->where('prestation_id', $prestationId)
            ->with('uploader:id,name')
            ->orderBy('created_at')
            ->get();
    }

    public function findForPrestation(int $prestationId, int $id): PrestationPiece
    {
        return PrestationPiece::query()
            ->where('prestation_id', $prestationId)
            ->with('uploader:id,name')
            ->findOrFail($id);
    }
}
