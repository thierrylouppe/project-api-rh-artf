<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface TypeDocumentInterface extends BaseInterface
{
    public function findByIds(array $ids): Collection;

    public function getObligatoires(): Collection;

    public function getOptionnels(): Collection;
}
