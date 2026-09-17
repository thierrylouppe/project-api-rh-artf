<?php

namespace App\Interfaces;

use App\Models\PrestationPiece;
use Illuminate\Support\Collection;

interface PrestationPieceInterface extends BaseInterface
{
    public function getByPrestation(int $prestationId): Collection;

    public function findForPrestation(int $prestationId, int $id): PrestationPiece;
}
