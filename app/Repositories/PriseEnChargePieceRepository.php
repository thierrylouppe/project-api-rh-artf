<?php

namespace App\Repositories;

use App\Interfaces\PriseEnChargePieceInterface;
use App\Models\PriseEnChargePiece;
use Illuminate\Support\Collection;

class PriseEnChargePieceRepository extends BaseRepository implements PriseEnChargePieceInterface
{
    protected function model(): string
    {
        return PriseEnChargePiece::class;
    }

    public function getByPriseEnCharge(int $priseEnChargeId): Collection
    {
        return PriseEnChargePiece::query()
            ->where('prise_en_charge_id', $priseEnChargeId)
            ->with('uploader:id,name')
            ->orderBy('created_at')
            ->get();
    }

    public function findForPriseEnCharge(int $priseEnChargeId, int $id): PriseEnChargePiece
    {
        return PriseEnChargePiece::query()
            ->where('prise_en_charge_id', $priseEnChargeId)
            ->with('uploader:id,name')
            ->findOrFail($id);
    }
}
