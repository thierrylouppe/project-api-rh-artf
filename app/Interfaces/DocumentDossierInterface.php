<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface DocumentDossierInterface extends BaseInterface
{
    public function getByDossier(int $dossierId): Collection;

    public function validerDocument(int $id, int $validateurId, ?string $commentaire): mixed;

    public function tousObligatoiresValides(int $dossierId): bool;
}
