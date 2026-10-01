<?php

namespace App\Interfaces;

use App\Models\PriseEnChargePiece;
use Illuminate\Support\Collection;

interface PriseEnChargePieceInterface extends BaseInterface
{
    public function getByPriseEnCharge(int $priseEnChargeId): Collection;

    public function findForPriseEnCharge(int $priseEnChargeId, int $id): PriseEnChargePiece;
}
