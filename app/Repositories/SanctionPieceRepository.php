<?php

namespace App\Repositories;

use App\Interfaces\SanctionPieceInterface;
use App\Models\SanctionPiece;
use Illuminate\Support\Collection;

class SanctionPieceRepository extends BaseRepository implements SanctionPieceInterface
{
    protected function model(): string
    {
        return SanctionPiece::class;
    }

    public function getBySanction(int $sanctionId): Collection
    {
        return SanctionPiece::query()
            ->where('sanction_id', $sanctionId)
            ->with('uploader:id,name')
            ->orderBy('created_at')
            ->get();
    }

    public function findForSanction(int $sanctionId, int $id): SanctionPiece
    {
        return SanctionPiece::query()
            ->where('sanction_id', $sanctionId)
            ->with('uploader:id,name')
            ->findOrFail($id);
    }
}
