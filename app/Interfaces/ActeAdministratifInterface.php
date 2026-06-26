<?php

namespace App\Interfaces;

use App\Enums\TypeActeAdministratif;
use App\Models\ActeAdministratif;
use Illuminate\Support\Collection;

interface ActeAdministratifInterface extends BaseInterface
{
    public function getByDossier(int $dossierId): Collection;

    public function signer(int $id, int $signataire): ActeAdministratif;

    public function genererNumero(TypeActeAdministratif $type): string;

    public function acteExistePourType(int $dossierId, string $typeActe): bool;
}
