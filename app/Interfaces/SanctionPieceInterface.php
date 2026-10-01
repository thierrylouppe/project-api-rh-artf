<?php

namespace App\Interfaces;

use App\Models\SanctionPiece;
use Illuminate\Support\Collection;

interface SanctionPieceInterface extends BaseInterface
{
    public function getBySanction(int $sanctionId): Collection;

    public function findForSanction(int $sanctionId, int $id): SanctionPiece;
}
