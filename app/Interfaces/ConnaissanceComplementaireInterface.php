<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ConnaissanceComplementaireInterface extends BaseInterface
{
    public function parEvaluation(int $evaluationId): Collection;
}
