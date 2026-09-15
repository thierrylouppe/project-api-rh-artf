<?php

namespace App\Interfaces;

use App\Models\AyantDroitPiece;
use Illuminate\Support\Collection;

interface AyantDroitPieceInterface extends BaseInterface
{
    public function getByAyantDroit(int $ayantDroitId): Collection;

    public function findForAyantDroit(int $ayantDroitId, int $id): AyantDroitPiece;
}
