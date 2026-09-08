<?php

namespace App\Interfaces;

use App\Models\TypeConge;
use Illuminate\Support\Collection;

interface TypeCongeInterface extends BaseInterface
{
    /** @return Collection<int, TypeConge> */
    public function getDebitantSolde(): Collection;
}
