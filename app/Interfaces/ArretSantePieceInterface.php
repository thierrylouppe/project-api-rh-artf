<?php

namespace App\Interfaces;

use App\Models\ArretSantePiece;
use Illuminate\Support\Collection;

interface ArretSantePieceInterface extends BaseInterface
{
    public function getByArret(int $arretId): Collection;

    public function findForArret(int $arretId, int $id): ArretSantePiece;
}
